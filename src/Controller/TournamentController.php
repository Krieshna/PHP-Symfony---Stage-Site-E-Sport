<?php

namespace App\Controller;

use DateTime;
use App\Entity\Tournament;
use App\Form\TournamentType;
use App\Entity\TeamInvitation;
use App\Entity\TournamentSeason;
use App\Form\TeamInvitationType;
use App\Entity\TournamentMatches;
use App\Repository\TeamRepository;
use App\Repository\SeasonRepository;
use App\Entity\TournamentApplication;
use App\Form\TournamentInvitationType;
use App\Form\TournamentApplicationType;
use App\Repository\TournamentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TournamentController extends AbstractController
{
    /**
     * Show Tournaments list
     * 
     * @Route("/tournament", name="tournament_index")
     */
    public function index(TournamentRepository $repo)
    {
        $tournois = $repo->findAll();
        return $this->render('tournament/index.html.twig',[
            'tournois' => $tournois
        ]);
    }

    /**
     * Handle Tournament creation
     *
     * @Route("/tournament/new", name="tournament_new")
     * @IsGranted("ROLE_USER")
     * 
     * @param Request $requet
     */
    public function new(Request $request, SeasonRepository $seasonRepository)
    {
        $tournoi = new Tournament;
        $form = $this-> createForm(TournamentType::class, $tournoi );
        $form -> handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $tournoi 
                ->setCreatedAt(new \DateTime())
                ->setCreator($this->getUser())
                ->setSeason($seasonRepository->findOneBy(['year' => '2020']) )
                ->setIsActive(1 )
            ;
            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($tournoi);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre tournoi a bien été crée."
            );               
            return $this -> redirectToRoute("tournament_show", [
                'slug' => $tournoi->getSlug()
            ] );
        }
        return $this->render('tournament/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     *  Show Tournament Pages
     * 
     *  @Route("/tournament/{slug}", name="tournament_show")
     *
     * @param Tournament $tournoi
     */
    public function show(Tournament $tournoi)
    {
        return $this->render('tournament/show.html.twig',[
            'tournoi' => $tournoi
        ]);
    }

    /**
     *  Modify Tournament Main Infos
     * 
     *  @Route("/tournament/{slug}/edit", name="tournament_edit")
     *  @Security("is_granted('ROLE_USER') and user === tournoi.getCreator()", message="Vous ne possédez pas les droits d'administrations pour ce tournoi. Vous ne pouvez donc pas le modifier")
     * 
     * @param Tournament $tournoi
     */
    public function edit(Tournament $tournoi, Request $request)
    {
        $form = $this-> createForm(TournamentType::class, $tournoi );
        $form -> handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($tournoi);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre tournoi a bien été crée."
            );               
            return $this -> redirectToRoute("tournament_show", [
                'slug' => $tournoi->getSlug()
            ] );
        }
        return $this->render('tournament/edit.html.twig',[
            'tournoi' => $tournoi,
            'form' => $form->createView()
        ]);
    }
    
   /**
    * Handle Tournament Invitation
    *
    * @Route("/tournament/{slug}/invitation", name="tournament_invitation")
    *  @Security("is_granted('ROLE_USER') and user === tournoi.getCreator()", message="Vous ne possédez pas les droits d'administrations pour ce tournoi.vous ne pouvez donc pas y inviter d'équipes")
    *
    * @param Tournament $tournoi    
    * @param Request $request
    */
    public function Invitation (Tournament $tournoi, Request $request){
        $invitation = new TournamentApplication;
        $form = $this-> createForm(TournamentInvitationType::class, $invitation );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation 
                -> setTournament($tournoi)
                -> setIsApplication(0)
                -> setIsActive(1)
                -> setApplicatedAt(new \DateTime)
            ;
            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($invitation);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre invitation a bien été envoyée à.".$invitation->getTeam()->getName()
            );               
            return $this -> redirectToRoute("tournament_index" );
        }
        return $this->render('tournament/invitation.html.twig',[         
            'form' => $form->createView(),
            'tournoi' => $tournoi
        ]);
    }
    
   /**
    * Handle Tournament Application
    *
    * @Route("/tournament/{slug}/join", name="tournament_application")
    * @Security("is_granted('ROLE_USER') ", message="Vous faites déjà partie de cette équipe")
    * @param Team $team
    * @param Request $request
    */
    public function Application (Tournament $tournoi, Request $request){
        $invitation = new TournamentApplication;
        $form = $this-> createForm(TournamentApplicationType::class, $invitation );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation  
                -> setTournament($tournoi)
                -> setIsApplication(1)
                -> setIsActive(1)
                -> setApplicatedAt(new \DateTime)
            ;
            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($invitation);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre application a bien été envoyée à".$invitation->getTournament()->getName()
            );               
            return $this -> redirectToRoute("tournament_index" );
        }
        return $this->render('tournament/application.html.twig',[         
            'form' => $form->createView(),
            'tournoi' => $tournoi
        ]);
    }
    
     /**
     * Accept tournament application / incitation  
     *  
     * @Route("/TournamentApplication/{id}/accept", name="tournament_application_accept")
     * 
     * @param TeamInvitation $application
     * @param TeamRepository $teamRepository
     * @return void
     */
    public function ApplictionAccept(TournamentApplication $application){
        $application ->setIsActive(0);
        
        $participation = new TournamentParticipation();
        $participation     
        -> setTeam($application->getTeam())
        ->setTournament($application->getTournament())
        ;        
        
        $manager = $this->getDoctrine()->getManager();
        $manager -> persist($application);
        $manager -> persist($participation);
        $manager -> flush();

        return $this -> redirectToRoute("tournament_index");
    }

    /**
     * Reject tournament application / incitation  
     *
     * @Route("/TournamentApplication/{id}/refuse", name="tournament_application_refuse")
     * 
     * @param TeamInvitation $application
     * @param TeamRepository $teamRepository
     * @return void
     */
    public function ApplictionRefuse(TournamentApplication $application){
        $application ->setIsActive(0); 

        $manager = $this->getDoctrine()->getManager();
        $manager -> persist($application);
        $manager -> flush();

        return $this -> redirectToRoute("tournament_index");
    }     

}

