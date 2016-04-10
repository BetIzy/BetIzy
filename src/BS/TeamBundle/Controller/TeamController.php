<?php
namespace BS\TeamBundle\Controller;

use BS\TeamBundle\Entity\Team;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use BS\ResultBundle\Repository;
/**
 * Created by PhpStorm.
 * User: Bj�rn
 * Date: 29/01/2016
 * Time: 17:16
 */
class TeamController extends Controller
{
    public function getAction()
    {
        ini_set('memory_limit', '-1');
        ini_set('max_execution_time', 18000);
        $repositoryResult = $this->getDoctrine()->getManager()->getRepository('BSResultBundle:Result');
        $resultList = $repositoryResult->getAllResult();
        //getting the title of a match
        $repositoryTeam = $this->getDoctrine()->getManager()->getRepository('BSTeamBundle:Team');
        foreach ($resultList as $result) {
            var_dump($result);
            if(substr_count($result->getEventLabel(), "-") > 1){
                $monfichier = fopen('erreur.txt', 'a+');
                fputs($monfichier, $result->getEventLabel() .  " => " . $result->getCompetitionID());
                var_dump($result->getEventLabel(). "=>". $result->getCompetitionID());
                fclose($monfichier);
            }
            else {
                List($dom, $ext) = explode("-", $result->getEventLabel());
                if (strpos($ext, "[") == false) {
                    // parsing of team name with match title
                    $teamList = $repositoryTeam->verifyDuplicate($dom, $result->getCompetitionID());
                    //verify if a home playing team is already present in the Team table
                    if (empty($teamList)) {
                        $team = new Team();
                        $team->setName($dom);
                        $team->setCompetitionId($result->getCompetitionID());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($team);
                        $em->flush();
                        // add in the Team table the home team
                    }
                    $teamList = $repositoryTeam->verifyDuplicate($ext, $result->getCompetitionID());
                    if (empty($teamList)) {
                        $team = new Team();
                        $team->setName($ext);
                        $team->setCompetitionId($result->getCompetitionID());
                        $em = $this->getDoctrine()->getManager();
                        $em->persist($team);
                        $em->flush();
                    }
                    $teamHome = $repositoryTeam->verifyDuplicate($dom, $result->getCompetitionID());
                    $teamOutside = $repositoryTeam->verifyDuplicate($ext, $result->getCompetitionID());
                    $result->setHomeTeamId($teamHome[0]->getId());
                    $result->setOutsideTeamId($teamOutside[0]->getId());
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($result);
                    $em->flush();
                }
            }
        }
        return new Response('Hello World');
    }

    public function initSeasonAction(){

        ini_set('max_execution_time', 18000);
        $teamRepository = $this->getDoctrine()->getManager()->getRepository('BSTeamBundle:Team');
        $teamList = $teamRepository->findAll();

        foreach($teamList as $team) {
            $team->setHomeVictory(0);
            $team->setHomeNull(0);
            $team->setHomeDefeat(0);
            $team->setOutsideVictory(0);
            $team->setOutsideNull(0);
            $team->setOutsideDefeat(0);
            $team->setRank(0);
            $team->setHomeRank(0);
            $team->setOutsideRank(0);
            $team->setPoints(0);
            $team->setHomePoints(0);
            $team->setOutsidePoints(0);
            $team->setSeries("");
            $em = $this->getDoctrine()->getManager();
            $em->persist($team);
            $em->flush();
        }
        return new Response('');
    }

    public function getTeamResultAction(){
        ini_set('max_execution_time', 18000);
        $this->initSeasonAction();
        $teamRepository = $this->getDoctrine()->getManager()->getRepository('BSTeamBundle:Team');
        $marketRepository = $this->getDoctrine()->getManager()->getRepository('BSResultBundle:MarketResult');
            $resultAndMarketList = $marketRepository->getResultJoinMarket();
            foreach($resultAndMarketList as $result){
                    $teamH = $teamRepository->getTeamByCompetition($result->getResult()->getCompetitionID(), $result->getResult()->getHomeTeamId());
                    $teamO = $teamRepository->getTeamByCompetition($result->getResult()->getCompetitionID(), $result->getResult()->getOutsideTeamId());
                    if(!empty($teamH) && !empty($teamO)) {
                        if ($result->getResultat() == 'Domicile') {
                            $teamH[0]->setHomePoints($teamH[0]->getHomePoints() + 3);
                            $teamH[0]->setHomeVictory($teamH[0]->getHomeVictory() + 1);
                            $teamH[0]->setPoints($teamH[0]->getPoints() + 3);
                            $teamH[0]->setSeries("V" . $teamH[0]->getSeries());

                            $teamO[0]->setOutsideDefeat($teamO[0]->getOutsideDefeat() + 1);
                            $teamO[0]->setSeries("D" . $teamO[0]->getSeries());
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($teamH[0]);
                            $em->persist($teamO[0]);
                        } else if ($result->getResultat() == 'Exterieur') {
                            $teamO[0]->setOutsidePoints($teamO[0]->getOutsidePoints() + 1);
                            $teamO[0]->setOutsideVictory($teamO[0]->getOutsideVictory() + 1);
                            $teamO[0]->setPoints($teamO[0]->getPoints() + 3);
                            $teamO[0]->setSeries("V" . $teamO[0]->getSeries());

                            $teamH[0]->setHomeDefeat($teamH[0]->getHomeDefeat() + 1);
                            $teamH[0]->setSeries("D" . $teamH[0]->getSeries());
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($teamH[0]);
                            $em->persist($teamO[0]);
                        } else {
                            $teamH[0]->setHomePoints($teamH[0]->getHomePoints() + 1);
                            $teamH[0]->setHomeNull($teamH[0]->getHomeNull() + 1);
                            $teamH[0]->setPoints($teamH[0]->getPoints() + 1);
                            $teamH[0]->setSeries("N" . $teamH[0]->getSeries());

                            $teamO[0]->setOutsidePoints($teamO[0]->getOutsidePoints() + 1);
                            $teamO[0]->setOutsideNull($teamO[0]->getOutsideNull() + 1);
                            $teamO[0]->setPoints($teamO[0]->getPoints() + 1);
                            $teamO[0]->setSeries("N" . $teamO[0]->getSeries());
                            $em = $this->getDoctrine()->getManager();
                            $em->persist($teamH[0]);
                            $em->persist($teamO[0]);
                        }
                        $em->flush();
                    }
            }
        return new Response('');
    }

    public function updateRankAction(){
        ini_set('max_execution_time', 18000);
        $teamRepository =$this->getDoctrine()->getManager()->getRepository("BSTeamBundle:Team");

        $competitionList = $teamRepository->getTeamGroupByCompetition();
        foreach($competitionList as $competition){
            $teamList = $teamRepository->getTeam($competition['competitionId']);

            $cpt = 1;
            //var_dump($teamList);
            foreach($teamList as $team){

                $team->setRank($cpt);
                $em = $this->getDoctrine()->getManager();
                $em->persist($team);
                $em->flush();
                //var_dump($team);
                $cpt++;
            }

        }
        $this->updateHomeRankAction();
        $this->updateOutsideRankAction();

        return new Response('');
    }

    public function updateHomeRankAction(){
        ini_set('max_execution_time', 18000);
        $teamRepository =$this->getDoctrine()->getManager()->getRepository("BSTeamBundle:Team");

        $competitionList = $teamRepository->getTeamGroupByCompetition();
        foreach($competitionList as $competition){
            $teamList = $teamRepository->getHomeTeam($competition['competitionId']);

            $cpt = 1;
            foreach($teamList as $team){

                $team->setHomeRank($cpt);
                $em = $this->getDoctrine()->getManager();
                $em->persist($team);
                $em->flush();
                //var_dump($team);
                $cpt++;
            }

        }
        return new Response('');
    }

    public function updateOutsideRankAction(){
        ini_set('max_execution_time', 18000);
        $teamRepository =$this->getDoctrine()->getManager()->getRepository("BSTeamBundle:Team");

        $competitionList = $teamRepository->getTeamGroupByCompetition();
        foreach($competitionList as $competition){
            $teamList = $teamRepository->getOutsideTeam($competition['competitionId']);

            $cpt = 1;
            foreach($teamList as $team){

                $team->setOutsideRank($cpt);
                $em = $this->getDoctrine()->getManager();
                $em->persist($team);
                $em->flush();
                //var_dump($team);
                $cpt++;
            }

        }
        return new Response('');
    }

    public function fusionTeamAction(){
        ini_set('max_execution_time', 18000);
        ini_set('memory_limit', '-1');
        $base1Rep = $this->getDoctrine()->getManager('base1')->getRepository("BSTeamBundle:Team");
        $teamListB1 = $base1Rep->findAll();
        $base2Rep = $this->getDoctrine()->getManager('base2')->getRepository("BSTeamBundle:Team");
        $teamListB2 = $base2Rep->findAll();

        foreach($teamListB1 as $team1){
            $team2 = $base2Rep->verifyDuplicate($team1->getName(),$team1->getCompetitionId());
            if(!empty($team2)){
                //base3 = base1 + base2
                $team = new Team();
                $team->setName($team1->getName());
                $team->setCompetitionId($team1->getCompetitionId());
                $hv = (int)($team1->getHomeVictory()) + (int)($team2[0]->getHomeVictory());
                $team->setHomeVictory((string)$hv);
                $team->setHomeDefeat($team1->getHomeDefeat() + $team2[0]->getHomeDefeat());
                $team->setHomeNull($team1->getHomeNull() + $team2[0]->getHomeNull());
                $team->setOutsideVictory($team1->getOutsideVictory() + $team2[0]->getOutsideVictory());
                $team->setOutsideNull($team1->getOutsideNull() + $team2[0]->getOutsideNull());
                $team->setOutsideDefeat($team1->getOutsideDefeat() + $team2[0]->getOutsideDefeat());
                $team->setHomePoints($team1->getHomePoints() + $team2[0]->getHomePoints());
                $team->setOutsidePoints($team1->getOutsidePoints() + $team2[0]->getOutsidePoints());
                $team->setPoints($team1->getPoints() + $team2[0]->getPoints());
                $team->setSeries($team2[0]->getSeries().$team1->getSeries());
                $em = $this->getDoctrine()->getManager('base3');
                $em->persist($team);
                $em->flush();
            }
            else{
                //base3 = base1
                $team = new Team();
                $team->setName($team1->getName());
                $team->setCompetitionId($team1->getCompetitionId());
                $team->setHomeVictory($team1->getHomeVictory());
                $team->setHomeDefeat($team1->getHomeDefeat());
                $team->setHomeNull($team1->getHomeNull());
                $team->setOutsideVictory($team1->getOutsideVictory());
                $team->setOutsideNull($team1->getOutsideNull());
                $team->setOutsideDefeat($team1->getOutsideDefeat());
                $team->setHomePoints($team1->getHomePoints());
                $team->setOutsidePoints($team1->getOutsidePoints());
                $team->setPoints($team1->getPoints());
                $team->setSeries($team1->getSeries());
                $em = $this->getDoctrine()->getManager('base3');
                $em->persist($team);
                $em->flush();
            }
        }

        foreach($teamListB2 as $team2){

            if(empty($team1 = $base1Rep->verifyDuplicate($team2->getName(),$team2->getCompetitionId()))){
                //base3 = base1 + base2
                $team = new Team();
                $team->setName($team2->getName());
                $team->setCompetitionId($team2->getCompetitionId());
                $team->setHomeVictory($team2->getHomeVictory());
                $team->setHomeDefeat($team2->getHomeDefeat());
                $team->setHomeNull($team2->getHomeNull());
                $team->setOutsideVictory($team2->getOutsideVictory());
                $team->setOutsideNull($team2->getOutsideNull());
                $team->setOutsideDefeat($team2->getOutsideDefeat());
                $team->setHomePoints($team2->getHomePoints());
                $team->setOutsidePoints($team2->getOutsidePoints());
                $team->setPoints($team2->getPoints());
                $team->setSeries($team2->getSeries());
                $em = $this->getDoctrine()->getManager('base3');
                $em->persist($team);
                $em->flush();
            }
        }

        return new Response('');
    }
}
?>