<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Entity\PasswordUpdate;
use App\Form\RegistrationType;
use App\Form\PasswordUpdateType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AccountController extends AbstractController
{    
    /**
    * Handle registration
    *
    * @Route("/register", name="account_register")
    *
    * @param Request $request
    * @param UserPasswordEncoderInterface $encoder
    * @return Response
    */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)    
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::Class, $user);  
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $hash = $encoder -> 
            encodePassword($user, $user->getHash());    
            $user -> setHash($hash);

            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($user);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre compte a bien été créé. Vous pouvez vous connecter."
            );      
            return $this->redirectToRoute('account_login');
        }        
        return $this->render('account/register.html.twig',[
            'form' => $form->CreateView(),   
        ]);
    }    
    
    /**
    * Handle connection
    *
    * @Route("/login", name="account_login")
    *
    * @param AuthenticationUtils $utils
    * @return void
    */    
    public function login(AuthenticationUtils $utils)
    {
    // Obtients une erreur de Login si elle il existe une
    $error = $utils->getLastAuthenticationError();
    // Retiens le dernier Username utiliser
    $username = $utils->getLastUsername();
        return $this->render('account/login.html.twig',[
            'error' => $error,
            'username' => $username,
        ]);
    }

    /**
    * Handle disconnection
    *
    * @IsGranted("ROLE_USER")
    * @Route("/logout", name="account_logout")
    *
    * @return Response
    */    
    public function logout()
    {
        return $this->render('home/index.html.twig');
    }
    
    /**
    * Handle User Profile
    *   
    * @IsGranted("ROLE_USER")     * 
    * @Route("/account", name="account_index")     
    * @return Response
    */
    public function myaccount()
    {
        return $this->render('user/index.html.twig',[
            'user' => $this->getUser()
        ]);
    }

    /**
    * Modify User Main Info
    *
    * @IsGranted("ROLE_USER")
    * @Route("/account/profile", name="account_profile")
    *
    * @param Request $request
    * @param UserPasswordEncoderInterface $encoder
    * @return void
    */
    public function profile(Request $request, UserPasswordEncoderInterface $encoder)    
    {
        $user = $this->getUser();
        $form = $this->createForm(AccountType::Class, $user);  
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($user);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre compte a bien été modifiée."
            );     
        }       
        return $this->render('account/profile.html.twig',[
            'form' => $form->CreateView(),   
        ]);
    }

    /**
    * Modify User Password
    *
    * @IsGranted("ROLE_USER") 
    * @Route("/account/password", name="account_password")
    *  
    * @param Request $request
    * @param UserPasswordEncoderInterface $encoder
    * @return void
    */
    public function passwordUpdate(Request $request, UserPasswordEncoderInterface $encoder){
        
        $passwordUpdate = new PasswordUpdate();        
        $user = $this->getUser();         
        $form = $this->createForm(PasswordUpdateType::Class, $passwordUpdate);  
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            if (!password_verify($passwordUpdate->getOldPassword(), $user->getHash()))
            {
                $form->get('oldPassword')->addError(new FormError("Le mot de passe que vous avez tapé n'est pas votre mot de passe actuel"));
            }
            else
            {
                $newPassword = $passwordUpdate->getNewPassword();
                $hash = $encoder->encodePassword($user,$newPassword);  
                $user -> setHash($hash); 
                
                $manager = $this->getDoctrine()->getManager();
                $manager -> persist($user);
                $manager -> flush();

                $this-> addFlash(
                    'success', 
                    "Votre mot de passe a bien été modifié !"
                );    
            }           
        }    
        return $this->render('account/passwordUpdate.html.twig',[
            'form' => $form->CreateView(),
        ]);
    }

    
}
     
