<?php

namespace App\Controller;

use App\Form\DateType;
use App\Form\ScoreType;
use App\Entity\Tournament;
use App\Service\MatchCreator;
use App\Entity\TournamentMatches;
use App\Repository\SeasonRepository;
use App\Repository\TournamentRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TournamentMatchesRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MatchesController extends AbstractController
{
  /**
     *  Create Matchs
     * 
     *  @Route("/tournament/{slug}/match/create", name="matchs_create")
     *  @Security("is_granted('ROLE_USER') and user === tournoi.getCreator()",
     *  message="Vous ne possédez pas les droits d'administrations pour ce tournoi.
     *  Vous ne pouvez donc pas créers les rencontres.")
     * 
     * @param Tournament $tournoi
     */
    public function create (Tournament $tournoi, MatchCreator $matchCreator, TournamentRepository $repo)
    {        

        $participants= $matchCreator->participants( $tournoi);
        
        for ($i = 0; $i <= 15 ; $i++) {
            $match = new TournamentMatches;
            // if ($i <= 7){
            //     $phase= 1;
            //     $round = $i+1;
            //     if (count($participants) > ($i+$i)){
            //         $team1= $participants[$i+$i];
            //     }
            //     else {
            //         $team1= null ;
            //     }
            //     if (count($participants) > ($i+$i+1)){
            //         $team2= $participants[$i+$i+1];
            //     }    
            //     else {
            //         $team2= null ;
            //         $i = 8;
            //     }
            // }   
            // elseif($i<=12){
            //     $phase= 2;
            //     $round = $i-8;
            //     $team1= null ;
            //     $team2= null ;
            // if (ceil(count($participants)/4) <= ($round)){
            //     $i =12;
            // }    
            // }       
            // elseif($i<=14){
            //     $phase= 3;
            //     $round = $i-12;
            //     $team1= null ;
            //     $team2= null ;
            //     if (ceil(count($participants)/8) < ($round)){
            //         $i =15;
            //     }  
            // }       
            // else{
            //     $phase= 4;
            //     $round = $i-14;
            //     $team1= null ;
            //     $team2= null;
            // }  
            
            $match
                ->setTournament($tournoi)
                ->setTeam1( $matchCreator->matchTeam1($i,$participants))
                ->setTeam2( $matchCreator->matchTeam2($i,$participants))
                ->setStartDate(new \DateTime)
                ->setPhase($matchCreator->matchPhase($i))
                ->setRound($matchCreator->matchRound($i))
            ;
            $manager = $this->getDoctrine()->getManager();
            $manager -> persist($match);
            $i = $matchCreator->matchRoundCheck($i,$participants,$matchCreator->matchRound($i));
        }
        $manager -> flush();

        return $this -> redirectToRoute("tournament_show", [
            'slug' => $tournoi->getSlug()
        ] );  
    }

    /**
     *  Edit Score
     * 
     *  @Route("/match/score/{id}", name="match_score")
     *  @Security("is_granted('ROLE_USER') and user === match.getTournament().getCreator()", 
     * message="Vous ne possédez pas les droits d'administrations pour ce tournoi. Vous ne pouvez donc pas créers les rencontres.")
     * 
     * @param Tournament $tournoi
     */
    public function score (TournamentMatches $match, TournamentMatchesRepository $MatchesRepository, Request $request)
    {
              
        $form = $this->createForm(ScoreType::Class, $match);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();      
            $manager -> persist($match);            

            $nextMatch  = $MatchesRepository
            ->findOneBy([
                'phase' => $match->getPhase()+1, 
                'round'=> ceil($match->getRound()/2)
                ]);
            
            // If NOT EVEN
            if ($match->getRound()%2 != 0){
                $nextMatch->setTeam1($match->getWinner());
            }
            else{
                $nextMatch->setTeam2($match->getWinner());
            }         
            
            $manager -> flush();
            
            $this-> addFlash(
                'success', 
                "Le score a bien été enregistrée"
            );               
            return $this -> redirectToRoute("tournament_show",[
                'slug' => $match->getTournament()->getSlug()
            ]);
        }
  
        return $this->render('matches/score.html.twig',[
            'form' => $form -> createView(), 
            'match' => $match
        ] );  
    }

    /**
     *  Edit date
     * 
     *  @Route("/match/date/{id}", name="match_date")
     *  @Security("is_granted('ROLE_USER') and user === match.getTournament().getCreator()", message="Vous ne possédez pas les droits d'administrations pour ce tournoi. Vous ne pouvez donc pas créers les rencontres.")
     * 
     * @param Tournament $tournoi
     */
    public function date (TournamentMatches $match, TournamentMatchesRepository $MatchesRepository, Request $request)
    {              
        $form = $this->createForm(DateType::Class, $match);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $this->getDoctrine()->getManager();                       
            $manager -> flush();
            
            $this-> addFlash(
                'success', 
                "La date a bien été modifiée"
            );               
            return $this -> redirectToRoute("tournament_show",[
                'slug' => $match->getTournament()->getSlug()
            ]);
        }
  
        return $this->render('matches/date.html.twig',[
            'form' => $form -> createView(), 
            'match' => $match
        ] );  
    }
}