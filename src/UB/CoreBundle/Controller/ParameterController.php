<?php

namespace UB\CoreBundle\Controller;



use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use \UB\CoreBundle\Form\ParameterType;
use UB\CoreBundle\Entity\Parameter;
use UB\CoreBundle\Form\ParameterEditType;
class ParameterController extends Controller
{
    public function indexAction()
    {
            // Pour récupérer la liste de toutes les annonces : on utilise findAll()
            $listParameters = $this->getDoctrine()
              ->getManager()
              ->getRepository('UBCoreBundle:Parameter')
              ->findAll()
            ;

            // L'appel de la vue ne change pas
            return $this->render('UBCoreBundle:Parameter:index.html.twig', array(
              'listParameters' => $listParameters,
            ));
    }
    
    public function addAction(Request $request) {
        $parameter = new Parameter();
        $form = $this->createForm(ParameterType::class, $parameter);
        if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
        $em = $this->getDoctrine()->getManager();
        $em->persist($parameter);
        $em->flush();

        $request->getSession()->getFlashBag()->add('notice', 'Parametre bien enregistré.');

        return $this->redirectToRoute('ub_core_parameter');
    }

    return $this->render('UBCoreBundle:Parameter:add.html.twig', array(
      'form' => $form->createView(),
    ));
    }
    
    public function editAction($id, Request $request)
    {
      $em = $this->getDoctrine()->getManager();

      $parameter = $em->getRepository('UBCoreBundle:Parameter')->find($id);

      if (null === $parameter) {
        throw new NotFoundHttpException("Le parametre d'id ".$id." n'existe pas.");
      }

      $form = $this->get('form.factory')->create(ParameterEditType::class, $parameter);

      if ($request->isMethod('POST') && $form->handleRequest($request)->isValid()) {
        // Inutile de persister ici, Doctrine connait déjà notre annonce
        $em->flush();

        $request->getSession()->getFlashBag()->add('notice', 'Parametre bien modifiée.');

        return $this->redirectToRoute('ub_core_edit_parameter', array('id' => $parameter->getId()));
      }

      return $this->render('UBCoreBundle:Parameter:edit.html.twig', array(
        'parameter' => $parameter,
        'form'   => $form->createView(),
      ));
    }
}
