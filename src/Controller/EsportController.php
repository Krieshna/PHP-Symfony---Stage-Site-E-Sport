<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class EsportController extends AbstractController
{
    /**
     * @Route("/esport", name="esport_infos")
     */
    public function esport()
    {
        return $this->render('esport/info.html.twig');
    }

    /**
     * @Route("/esport/team", name="esport_team")
     */
    public function team()
    {
        return $this->render('esport/team.html.twig');
    }

    /**
     * @Route("/esport/challenge", name="esport_challenge")
     */
    public function challenge()
    {
        return $this->render('esport/challenge.html.twig');
    }

        /**
     * @Route("/esport/challenge/show", name="esport_challenge_show")
     */
    public function challengeShow()
    {
        return $this->render('esport/show.html.twig');
    }





}
