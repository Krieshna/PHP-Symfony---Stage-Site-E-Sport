<?php

namespace App\Controller;

use App\Entity\Team;
use App\Entity\User;
use App\Form\TeamType;
use App\Service\Queries;
use App\Entity\TeamInvitation;
use App\Entity\TeamMembership;
use App\Form\TeamApplicationType;
use App\Repository\TeamRepository;
use App\Repository\TeamRoleRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TeamController extends AbstractController
{
    /**
     * Show Teams List
     * 
     * @Route("/team", name="team_index")
     */
    public function index(TeamRepository $repo)
    {
        $teams = $repo->findAll();
        return $this->render('team/index.html.twig',[
            'teams' => $teams
        ]);
    }

    /**
     * Handle new team creation
     *
     * @Route("/team/new", name="team_new")
     * @IsGranted("ROLE_USER")   
     * 
     * @param Request $request
     */
    public function new(Request $request, TeamRoleRepository $teamRoleRepository)
    {        
        $team = new Team();           
        $form = $this->createForm(TeamType::Class, $team);  

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $team
                ->setCreatedAt(new \DateTime())
                -> setIsActive(1)
            ;

            $membership = new TeamMembership();
            $membership 
                -> setTeam($team)
                -> setMember($this->getUser())
                -> setTeamRole($teamRoleRepository->findOneBy(['title' => 'Leader']))
            ;            

            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($membership);            
            $manager -> persist($team);
            $manager -> flush();

            $this-> addFlash(
                'success', 
                "Votre équipe a bien été crée."
            );               
            return $this -> redirectToRoute("team_show",[
                'slug' => $team->getSlug()
            ]);
        }
        return $this->render('team/new.html.twig',[
            'form' => $form->createView()
        ]);
    }
    
    /**
     * Show Team Profile
     *
     *  @Route("/team/{slug}", name="team_show")
     * 
     * @param Team $team
     */
    public function show(Team $team, Queries $queries)
    {
        return $this->render('team/show.html.twig',[
            'team' => $team,
            'matchs' => $queries->matchs($team),
        ]);   
    }

    /**
     * Modify Team Profile
     * 
     * @Route("/team/{slug}/edit", name="team_edit")
     * @Security("is_granted('ROLE_USER') and user === team.getLeader()", message="Cette equipe ne vous appartient pas, vous ne pouvez donc pas la modifier")
     * 
     * @param Team $team
     * @param Request $request
    */
    public function edit(Team $team,Request $request)
    {        
        $form = $this->createForm(TeamType::Class, $team);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $team ->setUpdatedAt(new \DateTime());

            $manager = $this->getDoctrine()->getManager();      
            $manager -> persist($team);
            $manager -> flush();
            
            $this-> addFlash(
                'success', 
                "L'équipe a bien été modifiée"
            );               
            return $this -> redirectToRoute("team_show",[
                'slug' => $team->getSlug()
            ]);
        }
        return $this->render('team/edit.html.twig',[
            'form' => $form -> createView(), 
            'team' => $team,
        ]);   
    }

    /**
     * Disable Team
     * 
     * @Route("/team/{slug}/delete", name="team_delete")
     * @Security("is_granted('ROLE_USER') and user === team.getLeader()", message="Cette équipe ne vous appartient pas, vous ne pouvez donc pas la supprimer")
     */
    public function disable (Team $team){
        $team
            -> setIsActive(0)
            ->setUpdatedAt(new \DateTime())
        ;
        $manager = $this->getDoctrine()->getManager();      
        $manager -> persist($team);
        $manager -> flush();

        return $this -> redirectToRoute("team_index");
    }

    /**
     * Remove a Member
     *
     * @Route("/membership/remove/{id}", name="membership_remove")
     * @Security("is_granted('ROLE_USER') and user === membership.getTeam().getLeader()", message="Cette équipe ne vous appartient pas, vous ne pouvez donc pas la supprimer")
     */
    public function removeMember(TeamMembership $membership ){
        $manager = $this->getDoctrine()->getManager();      
        $manager -> remove($membership);
        $manager -> flush();
        return $this -> redirectToRoute("team_show",[
            'slug' => $membership->getTeam()->getSlug()
        ]);
    }

    /**
    * Handle Team Invitation
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
    * Handle Team Application
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
     * Accept Application / Invitation
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
     * Reject Application / Invitation
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

}

