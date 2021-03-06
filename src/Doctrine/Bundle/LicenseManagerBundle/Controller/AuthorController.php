<?php
namespace Doctrine\Bundle\LicenseManagerBundle\Controller;

use Doctrine\Bundle\LicenseManagerBundle\Entity\Author;

use Sensio\Bundle\FrameworkExtraBundle\Configuration as Extra;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use Pagerfanta\Pagerfanta;

/**
 * Author Controller
 */
class AuthorController extends Controller
{
     /**
      * @Extra\Route("/licenses/authors/{id}/update", name="licenses_authors_update")
      * @Extra\Method("POST")
      */
    public function updateAction($id, Request $request)
    {
        $this->assertIsRole('ROLE_ADMIN');

        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $email = $request->request->get('email');

        $author = $entityManager->find('Doctrine\Bundle\LicenseManagerBundle\Entity\Author', $id);
        $author->setEmail($email);

        $entityManager->flush();

        $navigator = $this->container->get('doctrine_license_manager.page_nagivator');
        $navigator->setRequest($request);

        return $navigator->gotoProjectView($author->getProject()->getId());
    }

    /**
     * @Extra\Route("/licenses/authors/{id}", name="licenses_author_view")
     * @Extra\Method("GET")
     * @Extra\Template
     */
    public function viewAction($id, Request $request)
    {
        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $author = $entityManager->find('Doctrine\Bundle\LicenseManagerBundle\Entity\Author', $id);

        $revisions = $this->get('simplethings_entityaudit.reader')->findRevisions('Doctrine\Bundle\LicenseManagerBundle\Entity\Author', $id);

        $commits = $this->findCommitsByAuthor($author);
        $commits->setMaxPerPage(50);
        $commits->setCurrentPage($request->get('page', 1));

        $expected = hash_hmac('sha512', $id . $author->getEmail(), $this->container->getParameter('secret'));

        return array('author' => $author, 'revisions' => $revisions, 'commits' => $commits, 'expectedHash' => $expected);
    }

    private function findCommitsByAuthor(Author $author)
    {
        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');

        $dql = "SELECT c FROM Doctrine\Bundle\LicenseManagerBundle\Entity\Commit c WHERE c.author = ?1 ORDER BY c.created ASC";
        $query = $entityManager->createQuery($dql);
        $query->setParameter(1, $author->getId());

        $commits = new Pagerfanta(new DoctrineORMAdapter($query));

        return $commits;
    }

    /**
     * @Extra\Route("/licenses/authors/{id}/admin-approve", name="licenses_author_approve")
     * @Extra\Method("POST")
     */
    public function approveAction($id, Request $request)
    {
        $this->assertIsRole('ROLE_ADMIN');

        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $author = $entityManager->find('Doctrine\Bundle\LicenseManagerBundle\Entity\Author', $id);

        $author->approve();
        $entityManager->flush();

        $this->container->get('session')->setFlash('notice', 'Author ' . $author->getEmail() . ' was approved.');

        return $this->redirect($this->generateUrl('licenses_project_view', array('id' => $author->getProject()->getId())));
    }

    /**
     * @Extra\Route("/licenses/authors/{id}/approve", name="author_approve")
     * @Extra\Method({"GET", "POST"})
     * @Extra\Template
     */
    public function authorApproveAction($id, Request $request)
    {
        $entityManager = $this->container->get('doctrine.orm.default_entity_manager');
        $author = $entityManager->find('Doctrine\Bundle\LicenseManagerBundle\Entity\Author', $id);

        if (!$author) {
            throw $this->createNotFoundException();
        }

        $project = $author->getProject();

        $hash = $request->query->get('hash', '');
        $expected = hash_hmac('sha512', $id . $author->getEmail(), $this->container->getParameter('secret'));

        if ($expected != $hash) {
            throw $this->createNotFoundException();
        }

        $form = $this->createForm(new \Doctrine\Bundle\LicenseManagerBundle\Form\ApproveType(), $author);

        if ($request->getMethod() === 'POST') {
            $form->bind($request);

            if ($form->isValid()) {
                $entityManager->flush();
                $this->container->get('session')->setFlash('notice', 'Thank you for your response to the license change.');

                return $this->redirect($this->generateUrl('author_approve', array('id' => $id, 'hash' => $hash)));
            }
        }

        return array('form' => $form->createView(), 'author' => $author, 'expectedHash' => $expected, 'project' => $project);
    }

    private function assertIsRole($role)
    {
        if (!$this->container->get('security.context')->isGranted($role)) {
            throw new AccessDeniedHttpException();
        }
    }
}

