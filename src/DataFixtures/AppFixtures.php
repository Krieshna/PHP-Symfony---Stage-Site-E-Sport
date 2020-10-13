<?php

namespace App\DataFixtures;

use DateInterval;
use Faker\Factory;
use App\Entity\Role;
use App\Entity\Team;
use App\Entity\User;
use App\Entity\TournamentLeague;
use App\Entity\TournamentSeason;
use App\Entity\TeamRole;
use App\Entity\TeamMembership;
use App\Entity\Tournament;
use Cocur\Slugify\Slugify;
use App\Entity\TournamentParticipation;
use App\Entity\TournamentState;
use App\Repository\TeamRepository;
use App\Repository\TeamRoleRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{    
    // Encoder pour encrypter le password
    private $encoder;
    public function __construct(UserPasswordEncoderInterface $encoder){
        $this->encoder = $encoder; 
    }

    public function load(ObjectManager $manager)
    {   
        $faker = Factory::create('FR-fr');

        //Création de rôles 
        $adminRole = new Role ();
        $adminRole -> setTitle('ROLE_ADMIN');
        $manager-> persist($adminRole);

        //Création d'utilsateur avec le role Admin       
        $adminUser = new User();
        $adminUser        
            -> setUsername("admin")
            -> setPresentation('<p>' . join('</p><p>',$faker->paragraphs(3)).'</p>')
            -> setEmail("charles@symfony.com")
            -> setHash($this->encoder->encodePassword($adminUser,'admin'))
            -> setPicture('https://randomuser.me/api/portraits/men/' . $faker->numberBetween(1,99).'.jpg')
            -> addUserRole($adminRole)
        ;
        $manager-> persist($adminUser);

        // Créations d'utilisateurs
        for($i= 0;$i<=10;$i++){
            $user = new User();
            $genres =['male','female'];
            $genre = $faker->randomElement($genres);
            $picture = 'https://randomuser.me/api/portraits/' . 
            ($genre == 'male' ? 'men/':'women/') . 
            $faker->numberBetween(1,99).'.jpg';

            $hash= $this->encoder->encodePassword($user,'ok');
            
            $user  
                -> setUsername($faker->userName )
                -> setPresentation('<p>' . join('</p><p>',$faker->paragraphs(3)).'</p>')
                -> setEmail($faker->email)
                -> setHash($hash)
                -> setPicture($picture)
            ;

            $manager-> persist($user);
            $users[] = $user;
        }

        // Création de roles d'équipe
        $member = new TeamRole;
        $member -> setTitle('Member');
        $manager-> persist($member);
        $leader = new TeamRole;
        $leader -> setTitle('Leader');
        $manager-> persist($leader);

        // Création d'équipes   
        for($i= 0;$i<=30;$i++){  
            $team = new Team();
            $team  
                -> setName($faker->company )
                -> setTag ($faker->randomLetter.$faker->randomLetter.$faker->randomLetter) 
                -> setLogo($faker->imageUrl(350,350) )
                -> setDescription('<p>' . join('</p><p>',$faker->paragraphs(3)).'</p>')
                -> setIsActive(1)
                -> setRecruitStatus(1)
                -> setCreatedAt($faker->dateTimeBetween('-3 months'))
            ;

            // Ajout de membres à l'équipe
            shuffle($users);
            for($j=0;$j<=3;$j++){
                $membership = new TeamMembership();
                            
                if ($j == 0){
                    $role = $leader;
                }
                else{
                    $role = $member;
                }

                $membership 
                    -> setTeam($team)
                    -> setMember($users[$j])
                    -> setTeamRole($role)
                ;
                $manager-> persist($membership);
            }; 
            $manager-> persist($team);        
            $teams[] = $team;
        }

        // creation des ligues
        $LeagueTitle=['casual','private','official'];
        for  ($i= 0;$i<=2;$i++){
            $league = new TournamentLeague;
            $league -> setTitle( $LeagueTitle{$i});
            $manager-> persist($league);
            $leagues[]= $league;
        }

        // creation des ligues
        $SeasonTitle=['2018','2019','2020'];
        for  ($i= 0;$i<=2;$i++){
            $season = new TournamentSeason;
            $season -> setYear( $SeasonTitle{$i});
            $manager-> persist($season); 
            $seasons[]= $season;
        }

        // creation des statues de tournois
        $StateTitle=['Registering','Ongoing','Ended'];
        for  ($i= 0;$i<=2;$i++){
            $State = new TournamentState;
            $State -> setTitle( $StateTitle{$i});
            $manager-> persist($State); 
            $States[]= $State;            
        }

        // Création de tournoi
        for ($i= 0;$i<=4;$i++){
            $tournoi = new Tournament();

            $tournoi 
                -> setName($faker->sentence())
                -> setCreator($users[mt_rand(1,count($users)-1)])
                -> setDescription('<p>' . join('</p><p>',$faker->paragraphs(3)).'</p>')
                -> setLeague($leagues[mt_rand(1,count($leagues)-1)])
                -> setSeason($seasons[mt_rand(1,count($seasons)-1)])
                -> setState ($States[mt_rand(1,count($States)-1)])
                -> setCreatedAt(new \DateTime())
                -> setStartDate($faker->dateTimeBetween($startDate = 'now', $endDate = '3 months'))
                -> setEndDate($faker->dateTimeBetween($startDate = '3 months', $endDate = '10 months'))
            ;
            $manager-> persist($tournoi);

            // Inscription des équipes
            shuffle($teams);
            for ($k= 0;$k<=8;$k++){
                $participation = new TournamentParticipation();

                $participation 
                    -> setTournament($tournoi)
                    -> setTeam($teams[$k])
                ;
                $manager-> persist($participation);
            }
        }                
        $manager->flush();
    }
}

