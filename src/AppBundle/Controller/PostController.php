<?php

namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use AppBundle\Entity\Post;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\File\File;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Post controller.
 *
 * @Route("/post")
 */
class PostController extends Controller
{
    /**
     * Lists all Post entities.
     *
     * @Route("/", name="post_index")
     * @Method("GET")
     * @Security("has_role('ROLE_ADMIN')")
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $posts = $em->getRepository('AppBundle:Post')->findAll();

        return $this->render('post/index.html.twig', array(
            'posts' => $posts,
        ));
    }

    /**
     * Creates a new Post entity.
     *
     * @Route("/new", name="post_new")
     * @Method({"GET", "POST"})
     */
    public function newAction(Request $request)
    {
        $post = new Post();

        $user = $this->get('security.token_storage')->getToken()->getUser();
        $post->setAuthor($user);
        $form = $this->createForm('AppBundle\Form\PostType', $post);
        $form->handleRequest($request);
        $images = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $postImages = $post->getPostImages();
            $post->setPostImages(new ArrayCollection());
            foreach ($postImages as $image) {
                $fileName = md5(uniqid()).'.'.$image->guessExtension();
                if ($image->move(
                $this->container->getParameter('post_image').$post->getTitle(),
                $fileName)) {
                    // $images[] = $fileName;
                  $post->addPostImage(array('fileName' => $fileName));
                }
            }
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('post_show', array('id' => $post->getId()));
        }

        return $this->render('post/new.html.twig', array(
            'post' => $post,
            'form' => $form->createView(),
        ));
    }

    private function checkAction(Post $post)
    {
        $post_author = $post->getAuthor();
        $userEntity = $this->getUser();

        if (!$this->get('security.authorization_checker')->isGranted('ROLE_ADMIN') && $post_author !== $userEntity) {
            return $this->render('errors/error_role.html.twig');
        }
    }

    /**
     * Finds and displays a Post entity.
     *
     * @Route("/{id}", name="post_show")
     * @Method("GET")
     */
    public function showAction(Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);

        $post_author = $post->getAuthor();
        $userEntity = $this->getUser();
        // $images = [];
        // foreach ($post->getPostImages() as $image) {
        //   var_dump($image);die;
        //     $images[] = new File($this->container->getParameter('post_image').$post->getTitle().'/'.$image);
        // }
        // $post->setPostImages($images);
        if ($this->checkAction($post)) {
            return $this->checkAction($post);
        }

        return $this->render('post/show.html.twig', array(
            'post' => $post,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Post entity.
     *
     * @Route("/{id}/edit", name="post_edit")
     * @Method({"GET", "POST"})
     */
    public function editAction(Request $request, Post $post)
    {
        $deleteForm = $this->createDeleteForm($post);
        $editForm = $this->createForm('AppBundle\Form\PostType', $post);
        $original_images = $post->getPostImages();
        $editForm->handleRequest($request);

        // $original_images = clone $post->getPostImages();
        var_dump($original_images); // object

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $postImages = $post->getPostImages();
            $post->setPostImages([]);
            var_dump($original_images); // not same as 1st dump
            die;

            foreach ($postImages as $image) {
                $fileName = md5(uniqid()).'.'.$image->guessExtension();
                if ($image->move(
                $this->container->getParameter('post_image').$post->getTitle(),
                $fileName)) {
                    // $images[] = $fileName;
                  $post->addPostImage(array('fileName' => $fileName));


                }
            }
            $em->persist($post);
            $em->flush();

            return $this->redirectToRoute('post_edit', array('id' => $post->getId()));
        }

        if ($this->checkAction($post)) {
            return $this->checkAction($post);
        }

        return $this->render('post/edit.html.twig', array(
            'post' => $post,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a Post entity.
     *
     * @Route("/{id}", name="post_delete")
     * @Method("DELETE")
     */
    public function deleteAction(Request $request, Post $post)
    {
        $form = $this->createDeleteForm($post);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($post);
            $em->flush();
        }

        if ($this->checkAction($post)) {
            return $this->checkAction($post);
        }

        return $this->redirectToRoute('homepage');
    }

    /**
     * Creates a form to delete a Post entity.
     *
     * @param Post $post The Post entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm(Post $post)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('post_delete', array('id' => $post->getId())))
            ->setMethod('DELETE')
            ->getForm()
        ;
    }
}
