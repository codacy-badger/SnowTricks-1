<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\ImageUploader;
use App\Entity\User;
use App\Form\UserType;

class SecurityController extends Controller
{
    public function login(Request $request, AuthenticationUtils $authenticationUtils)
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', array(
          'last_username' => $lastUsername,
          'error'         => $error,
      ));
    }

    public function register(Request $request, ImageUploader $uploader, UserPasswordEncoderInterface $encoder)
    {
        $manager = $this->getDoctrine()->getManager();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getUserPhoto()->getAdress() !== null) {
                $filename = $uploader->upload($user->getUserPhoto()->getAdress(), $this->getParameter('users_photos_directory'));
                $user->getUserPhoto()->setAdress($filename);
                $user->getUserPhoto()->setUser($user);
            } elseif ($user->getUserPhoto()->getAdress() === null) {
                $user->setUserPhoto(null);
            }
            $user->setPassword($encoder->encodePassword($user, $user->getPassword()));
            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
            'notice',
            'Your account has been createdan activation link has been sent to '.$user->getEmail()
          );
            return $this->redirect($this->generateUrl('index').'#content');
        }

        return $this->render('security/register.html.twig', array('form' => $form->createView()));
    }
}
