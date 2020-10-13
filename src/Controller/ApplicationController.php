<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\Tournament;
use App\Entity\TeamInvitation;
use App\Entity\TeamMembership;
use App\Form\TeamInvitationType;
use App\Form\TeamApplicationType;
use App\Repository\RoleRepository;
use App\Repository\TeamRepository;
use App\Entity\TournamentApplication;
use App\Form\TournamentInvitationType;
use App\Repository\TeamRoleRepository;
use App\Entity\TournamentParticipation;
use App\Form\TournamentApplicationType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApplicationController extends AbstractController
{
 
   /**
    * Permet d'afficher et gérer le formulaire d'invitation au sein d'une équipe
    *
    * @Route("/team/{slug}/invitation", name="team_invitation")
    * @Security("is_granted('ROLE_USER') and user === team.getLeader()", message="Cette équipe ne vous appartient pas, vous ne pouvez donc pas y inviter d'autres membres")
    *
    * @param Team $team
    * @param Request $request
    */
    public function TeamInvitation (Team $team, Request $request){
        $invitation = new TeamInvitation;
        $form = $this-> createForm(TeamInvitationType::class, $invitation );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation 
                -> setTeam($team)
                -> setIsApplication(0)
                -> setIsActive(1)
                -> setInvitedAt(new \DateTime)
            ;
            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($invitation);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre invitation a bien été envoyée à.".$invitation->getUser()->getUserName()
            );               
            return $this -> redirectToRoute("team_show", [
                'slug' => $team->getSlug()
            ] );
        }
        return $this->render('team/invitation.html.twig',[         
            'form' => $form->createView(),
            'team' => $team
        ]);
    }

   /**
    * Permet d'afficher et gérer le formulaire d'application au sein d'une équipe
    *
    * @Route("/team/{slug}/join", name="team_application")
    * @Security("is_granted('ROLE_USER') and team.getRecruitStatus()==1", message="Cette equipe ne recrute actuellement plus")
    * @Security("is_granted('ROLE_USER') and team.isMember(user)==false", message="Vous faites déjà partie de cette équipe")
    * @param Team $team
    * @param Request $request
    */
    public function TeamApplication (Team $team, Request $request){
        $invitation = new TeamInvitation;
        $form = $this-> createForm(TeamApplicationType::class, $invitation );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation 
                -> setUser($this->getUser())
                -> setTeam($team)
                -> setIsApplication(1)
                -> setIsActive(1)
                -> setInvitedAt(new \DateTime)
            ;
            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($invitation);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre application a bien été envoyée à".$invitation->getTeam()->getName()
            );               
            return $this -> redirectToRoute("team_show", [
                'slug' => $team->getSlug()
            ] );
        }
        return $this->render('team/application.html.twig',[         
            'form' => $form->createView(),
            'team' => $team
        ]);
    }

    /**
     * Valide l'application ou l'invitation
     *
     * @Route("/teamapplication/{id}/accept", name="team_application_accept")
     * 
     * @param TeamInvitation $application
     * @param TeamRepository $teamRepository
     * @return void
     */
    public function TeamApplictionAccept(TeamInvitation $application, TeamRoleRepository $teamRoleRepository){
        $application ->setIsActive(0);
        
        $membership = new TeamMembership();
        $membership     
        -> setTeam($application->getTeam())
        -> setMember($application->getUser())
        -> setTeamRole($teamRoleRepository->findOneBy(['title' => 'Member']))
        ;        
        
        $manager = $this->getDoctrine()->getManager();
        $manager -> persist($application);
        $manager -> persist($membership);
        $manager -> flush();

        return $this -> redirectToRoute("team_index");
    }

    /**
     * Refuse l'application ou l'invitation
     *
     * @Route("/TeamApplication/{id}/refuse", name="team_application_refuse")
     * 
     * @param TeamInvitation $application
     * @param TeamRepository $teamRepository
     * @return void
     */
    public function TeamApplictionRefuse(TeamInvitation $application){
        $application ->setIsActive(0); 

        $manager = $this->getDoctrine()->getManager();
        $manager -> persist($application);
        $manager -> flush();

        return $this -> redirectToRoute("team_index");
    }
    
   /**
    * Permet d'afficher et gérer le formulaire d'invitation au sein d'un tournoi
    *
    * @Route("/tournament/{slug}/invitation", name="tournament_invitation")
    *  @Security("is_granted('ROLE_USER') and user === tournoi.getCreator()", message="Vous ne possédez pas les droits d'administrations pour ce tournoi.vous ne pouvez donc pas y inviter d'équipes")
    *
    * @param Tournament $tournoi    
    * @param Request $request
    */
    public function TournamentInvitation (Tournament $tournoi, Request $request){
        $invitation = new TournamentApplication;
        $form = $this-> createForm(TournamentInvitationType::class, $invitation );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation 
                -> setTournament($tournoi)
                // -> setIsApplication(0)
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
    * Permet d'afficher et gérer le formulaire d'application à un tournoi
    *
    * @Route("/tournament/{slug}/join", name="tournament_application")
    * @Security("is_granted('ROLE_USER') ", message="Vous faites déjà partie de cette équipe")
    * @param Team $team
    * @param Request $request
    */
    public function TournamentApplication (Tournament $tournoi, Request $request){
        $invitation = new TournamentApplication;
        $form = $this-> createForm(TournamentApplicationType::class, $invitation );
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation  
                -> setTournament($tournoi)
                // -> setIsApplication(1)
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
     * Valide l'application ou l'invitation au tournoi
     *  
     * @Route("/TournamentApplication/{id}/accept", name="team_application_accept")
     * 
     * @param TeamInvitation $application
     * @param TeamRepository $teamRepository
     * @return void
     */
    public function TournamentApplictionAccept(TournamentApplication $application){
        $application ->setIsActive(0);
        
        $participation = new TournamentParticipation();
        $participation     
        -> setTeam($application->getTeam())
        ;        
        
        $manager = $this->getDoctrine()->getManager();
        $manager -> persist($application);
        $manager -> persist($participation);
        $manager -> flush();

        return $this -> redirectToRoute("tournament_index");
    }

    /**
     * Refuse l'application ou l'invitation au tournoi
     *
     * @Route("/TournamentApplication/{id}/refuse", name="team_application_refuse")
     * 
     * @param TeamInvitation $application
     * @param TeamRepository $teamRepository
     * @return void
     */
    public function TournamentApplictionRefuse(TournamentApplication $application){
        $application ->setIsActive(0); 

        $manager = $this->getDoctrine()->getManager();
        $manager -> persist($application);
        $manager -> flush();

        return $this -> redirectToRoute("tournament_index");
    }

}
