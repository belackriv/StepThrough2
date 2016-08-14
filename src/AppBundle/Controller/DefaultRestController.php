<?php

namespace AppBundle\Controller;

use AppBundle\Library\Utilities;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use FOS\RestBundle\Controller\Annotations AS Rest;
use FOS\RestBundle\Controller\FOSRestController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Doctrine\Common\Collections\ArrayCollection;


class DefaultRestController extends FOSRestController
{
    use Mixin\RestPatchMixin;
    use Mixin\UpdateAclMixin;
    use Mixin\WampUpdatePusher;

    /**
     * @Rest\Get("/tid")
     * @Rest\View(template=":default:list_travelerid.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listTravelerIdAction()
    {
    	 $items = $this->getDoctrine()
        ->getRepository('AppBundle:TravelerId')
        ->findAll();

        return array('list'=>$items);
    }

    /**
     * @Rest\Get("/tid/{id}")
     * @Rest\View(template=":default:get_travelerid.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getTravelerIdAction(\AppBundle\Entity\TravelerId $travelerId)
    {
        return $travelerId;
    }

    /**
     * @Rest\Post("/tid")
     * @Rest\View(template=":default:create_travelerid.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("travelerId", converter="fos_rest.request_body")
     */
    public function createTravelerIdAction(\AppBundle\Entity\TravelerId $travelerId)
    {
    	$em = $this->getDoctrine()->getManager();
	    $em->persist($travelerId);
	    $em->flush();
        return $travelerId;
    }

    /**
     * @Rest\Put("/tid/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("travelerId", converter="fos_rest.request_body")
     */
    public function updateTravelerIdAction(\AppBundle\Entity\TravelerId $travelerId)
    {
        $em = $this->getDoctrine()->getManager();
        $em->merge($travelerId);
        $em->flush();
        return $travelerId;
    }

     /**
     * @Rest\Patch("/tid/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("travelerId", converter="fos_rest.request_body")
     */
    public function patchTravelerIdAction(\AppBundle\Entity\TravelerId $travelerId, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $liveTravelerId = $em->getRepository('AppBundle:TravelerId')->findOneById($id);
        $this->patchEntity($liveTravelerId, $travelerId);
        $em->flush();
        $this->pushUpdate($liveTravelerId);
        return $travelerId;
    }

    /**
     * @Rest\Get("/department")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listDepartmentAction()
    {
        $items = $this->getDoctrine()
        ->getRepository('AppBundle:Department')
        ->findAll();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $item){
            if (true === $authorizationChecker->isGranted('VIEW', $item)) {
                $itemlist[] = $item;
            }
        }

        return array('list'=>$itemlist);
    }

    /**
     * @Rest\Get("/department/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getDepartmentAction(\AppBundle\Entity\Department $department)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $department)){
            return $department;
        }else{
            throw $this->createNotFoundException('Department #'.$department->getId().' Not Found');
        }
    }

    /**
     * @Rest\Post("/department")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("department", converter="fos_rest.request_body")
     */
    public function createDepartmentAction(\AppBundle\Entity\Department $department)
    {
        if($this->get('security.authorization_checker')->isGranted('CREATE', $department)){
            $em = $this->getDoctrine()->getManager();
            $em->persist($department);
            $em->flush();
            $this->updateAclByRoles($department, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
            return $department;
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Get("/office")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "ListOffices"})
     */
    public function listOfficeAction()
    {
        $offices = $this->getDoctrine()
        ->getRepository('AppBundle:Office')
        ->findAll();

        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($offices as $office){
            foreach($office->getDepartments() as $dept){
                foreach($dept->getMenuItems() as $item){
                    if($item->getParent() !== null){
                        $dept->removeMenuItem($item);
                    }
                    $granted = $authorizationChecker->isGranted('VIEW', $item->getMenuLink());
                    if (false === $granted) {
                        $dept->removeMenuItem($item);
                    }
                    foreach($item->getChildren() as $child){
                        $granted = $authorizationChecker->isGranted('VIEW', $child->getMenuLink());
                        if (false === $granted) {
                            $item->removeChild($child);
                        }
                    }
                }
            }
        }

        return ['total_count'=> count($offices), 'total_items' => count($offices), 'list'=>$offices];
    }

    /**
     * @Rest\Get("/office/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getOfficeAction(\AppBundle\Entity\Office $office)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $office)){
            return $office;
        }else{
            throw $this->createNotFoundException('Office #'.$office->getId().' Not Found');
        }
    }

    /**
     * @Rest\Post("/office")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("office", converter="fos_rest.request_body")
     */
    public function createOfficeAction(\AppBundle\Entity\Office $office)
    {
        if($this->get('security.authorization_checker')->isGranted('CREATE', $office)){
            $em = $this->getDoctrine()->getManager();
            $em->persist($office);
            $em->flush();
            return $office;
            $this->updateAclByRoles($office, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Get("/menu_item")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"MenuItem"})
     */
    public function listMenuItemAction(Request $request)
    {
        $page = (int)$request->query->get('page') - 1;
        $perPage =(int)$request->query->get('per_page');
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(mi.id)')
            ->from('AppBundle:MenuItem', 'mi');

        $totalItems = $qb->getQuery()->getSingleScalarResult();

        Utilities::setupSearchableEntityQueryBuild($qb, $request);

        $totalCount = $qb->getQuery()->getSingleScalarResult();

        $qb->select('mi')
            ->orderBy('mi.id', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($page*$perPage);

        $items = $qb->getQuery()->getResult();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $menuItem){
            if (true === $authorizationChecker->isGranted('VIEW', $menuItem->getMenuLink())) {
                $itemlist[] = $menuItem;
            }
        }

        return ['total_count'=> (int)$totalCount, 'total_items' => (int)$totalItems, 'list'=>$itemlist];
    }

    /**
     * @Rest\Get("/menu_item/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"MenuItem"})
     */
    public function getMenuItemAction(\AppBundle\Entity\MenuItem $menuItem)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $menuItem)){
            return $menuItem;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$menuItem->getId().' Not Found');
        }
    }

    /**
     * @Rest\Post("/menu_item")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"MenuItem"})
     * @ParamConverter("menuItem", converter="fos_rest.request_body")
     */
    public function createMenuItemAction(\AppBundle\Entity\MenuItem $menuItem)
    {
    	if($this->get('security.authorization_checker')->isGranted('CREATE', $menuItem)){
            $em = $this->getDoctrine()->getManager();
            $em->persist($menuItem);
            $em->flush();
            $this->updateAclByRoles($menuItem, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
            return $menuItem;
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Put("/menu_item/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"MenuItem"})
     * @ParamConverter("menuItem", converter="fos_rest.request_body")
     */
    public function updateMenuItemAction(\AppBundle\Entity\MenuItem $menuItem)
    {
        if($this->get('security.authorization_checker')->isGranted('EDIT', $menuItem)){
            $em = $this->getDoctrine()->getManager();
            $em->merge($menuItem);
            $em->flush();
            return $menuItem;
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Delete("/menu_item/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"MenuItem"})
     */
    public function deleteMenuItemAction(\AppBundle\Entity\MenuItem $menuItem)
    {
        if($this->get('security.authorization_checker')->isGranted('DELETE', $menuItem)){
            $em = $this->getDoctrine()->getManager();
            $em->remove($menuItem);
            $em->flush();
            return $menuItem;
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Get("/menu_link")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listMenuLinkAction(Request $request)
    {
        $items = $this->getDoctrine()
        ->getRepository('AppBundle:MenuLink')
        ->findAll();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $item){
            if (true === $authorizationChecker->isGranted('VIEW', $item)) {
                $itemlist[] = $item;
            }
        }

        return ['total_count'=> count($itemlist), 'total_itemlist' => count($itemlist), 'list'=>$itemlist];
    }

    /**
     * @Rest\Get("/menu_link/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getMenuLinkAction(\AppBundle\Entity\MenuLink $menuLink)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $menuLink)){
            return $menuLink;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$menuLink->getId().' Not Found');
        }
    }


    /**
     * @Rest\Get("/myself")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "GetMyself"})
     */
    public function getMyself(Request $request)
    {
        $session = $request->getSession();
        $myself = $this->getUser();
        if($session->get('currentDepartmentId')){
            $department = $this->getDoctrine() ->getRepository('AppBundle:DepartMent')
                ->find($session->get('currentDepartmentId'));
            $myself->currentDepartment = $department;
        }else{
            $myself->currentDepartment = $myself->getDefaultDepartment();
        }
        $myself->appMessage = 'Test Message';
        $myself->roleHierarchy = $this->get('security.role_hierarchy')->fetchRoleHierarchy();
        return $myself;
    }

    /**
     * @Rest\Put("/myself/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default", "GetMyself"})
     * @ParamConverter("myself", converter="fos_rest.request_body")
     */
    public function updateMyself(\AppBundle\Entity\User $myself, Request $request)
    {
        $session = $request->getSession();
        $session->set('currentDepartmentId', $myself->currentDepartment->getId());
        return $myself;
    }


    /**
     * @Rest\Get("/user")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listUserAction(Request $request)
    {
        $page = (int)$request->query->get('page') - 1;
        $perPage =(int)$request->query->get('per_page');
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(u.id)')
            ->from('AppBundle:User', 'u');

        $totalItems = $qb->getQuery()->getSingleScalarResult();

        Utilities::setupSearchableEntityQueryBuild($qb, $request);

        $totalCount = $qb->getQuery()->getSingleScalarResult();

        $qb->select('u')
            ->orderBy('u.id', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($page*$perPage);

        $items = $qb->getQuery()->getResult();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $item){
            if (true === $authorizationChecker->isGranted('VIEW', $item)){
                $itemlist[] = $item;
            }
        }

        return ['total_count'=> (int)$totalCount, 'total_items' => (int)$totalItems, 'list'=>$itemlist];
    }

    /**
     * @Rest\Get("/user/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getUserAction(\AppBundle\Entity\User $user)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $user)){
            return $user;
        }else{
            throw $this->createNotFoundException('User #'.$user->getId().' Not Found');
        }
    }

    /**
     * @Rest\Post("/user")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function createUserAction(\AppBundle\Entity\User $user)
    {
        if($this->get('security.authorization_checker')->isGranted('CREATE', $user)){
            $em = $this->getDoctrine()->getManager();

            $encoder = $this->container->get('security.password_encoder');
            $encoded = $encoder->encodePassword($user, $user->getPassword());
            $user->setPassword($encoded);

            $em->persist($user);
            foreach($user->getUserRoles() as $userRole){
                $userRole->setUser($user);
                $em->persist($userRole);
            }
            $em->flush();
            $this->updateAclByRoles($user, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
            foreach($user->getUserRoles() as $userRole){
                $this->updateAclByRoles($userRole, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
            }
            return $user;
        }else{
             throw $this->createAccessDeniedException();
        }


    }

    /**
     * @Rest\Put("/user/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("user", converter="fos_rest.request_body")
     */
    public function updateUserAction(\AppBundle\Entity\User $user)
    {
        if($this->get('security.authorization_checker')->isGranted('EDIT', $user)){
            $em = $this->getDoctrine()->getManager();
            if($user->newPassword){
                $encoder = $this->container->get('security.password_encoder');
                $encoded = $encoder->encodePassword($user, $user->newPassword);
                $user->setPassword($encoded);
            }

            $em->merge($user);
            foreach($user->getUserRoles() as $userRole){
                $userRole->setUser($user);
                $em->persist($userRole);
            }
            $em->flush();
            return $user;
        }else{
              throw $this->createAccessDeniedException();
        }
    }


    /**
     * @Rest\Delete("/user_role/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function deleteUserRoleAction(\AppBundle\Entity\UserRole $userRole)
    {
        if($this->get('security.authorization_checker')->isGranted('DELETE', $userRole)){
            $em = $this->getDoctrine()->getManager();
            $em->remove($userRole);
            $em->flush();
            return $role;
        }else{
            throw $this->createAccessDeniedException();
        }
    }




    /**
     * @Rest\Get("/role")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listRoleAction(Request $request)
    {
        $page = (int)$request->query->get('page') - 1;
        $perPage =(int)$request->query->get('per_page');
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from('AppBundle:Role', 'r');

        $totalItems = $qb->getQuery()->getSingleScalarResult();

        Utilities::setupSearchableEntityQueryBuild($qb, $request);

        $totalCount = $qb->getQuery()->getSingleScalarResult();

        $qb->select('r')
            ->orderBy('r.id', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($page*$perPage);

        $items = $qb->getQuery()->getResult();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $item){
            if (true === $authorizationChecker->isGranted('VIEW', $item)){
                $itemlist[] = $item;
            }
        }

        return ['total_count'=> (int)$totalCount, 'total_items' => (int)$totalItems, 'list'=>$itemlist];
    }

    /**
     * @Rest\Get("/role/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getRoleAction(\AppBundle\Entity\Role $role)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $role)){
            return $role;
        }else{
            throw $this->createNotFoundException('Role #'.$role->getId().' Not Found');
        }
    }

    /**
     * @Rest\Get("/on_site_printer")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listOnSitePrinterAction(Request $request)
    {
        $page = (int)$request->query->get('page') - 1;
        $perPage =(int)$request->query->get('per_page');
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(osp.id)')
            ->from('AppBundle:OnSitePrinter', 'osp');

        $totalItems = $qb->getQuery()->getSingleScalarResult();

        Utilities::setupSearchableEntityQueryBuild($qb, $request);

        $totalCount = $qb->getQuery()->getSingleScalarResult();

        $qb->select('osp')
            ->orderBy('osp.id', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($page*$perPage);

        $items = $qb->getQuery()->getResult();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $item){
            if (true === $authorizationChecker->isGranted('VIEW', $item)){
                $itemlist[] = $item;
            }
        }

        return ['total_count'=> (int)$totalCount, 'total_items' => (int)$totalItems, 'list'=>$itemlist];
    }

    /**
     * @Rest\Get("/on_site_printer/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getOnSitePrinterAction(\AppBundle\Entity\OnSitePrinter $onSitePrinter)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $onSitePrinter)){
            return $onSitePrinter;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$onSitePrinter->getId().' Not Found');
        }
    }

    /**
     * @Rest\Post("/on_site_printer")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("onSitePrinter", converter="fos_rest.request_body")
     */
    public function createOnSitePrinterAction(\AppBundle\Entity\OnSitePrinter $onSitePrinter)
    {
        if($this->get('security.authorization_checker')->isGranted('CREATE', $onSitePrinter)){
            $em = $this->getDoctrine()->getManager();
            $em->persist($onSitePrinter);
            $em->flush();
            $this->updateAclByRoles($onSitePrinter, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
            return $onSitePrinter;
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Put("/on_site_printer/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("onSitePrinter", converter="fos_rest.request_body")
     */
    public function updateOnSitePrinterAction(\AppBundle\Entity\OnSitePrinter $onSitePrinter)
    {
        if($this->get('security.authorization_checker')->isGranted('EDIT', $onSitePrinter)){
            $em = $this->getDoctrine()->getManager();
            $em->merge($onSitePrinter);
            $em->flush();
            return $onSitePrinter;
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Delete("/on_site_printer/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function deleteOnSitePrinterAction(\AppBundle\Entity\OnSitePrinter $onSitePrinter)
    {
        if($this->get('security.authorization_checker')->isGranted('DELETE', $onSitePrinter)){
            $em = $this->getDoctrine()->getManager();
            $em->remove($onSitePrinter);
            $em->flush();
            return $onSitePrinter;
        }else{
             throw $this->createAccessDeniedException();
        }
    }

    /**
     * @Rest\Get("/label")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listLabelAction(Request $request)
    {
        $page = (int)$request->query->get('page') - 1;
        $perPage =(int)$request->query->get('per_page');
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(l.id)')
            ->from('AppBundle:Label', 'l');

        $totalItems = $qb->getQuery()->getSingleScalarResult();

        Utilities::setupSearchableEntityQueryBuild($qb, $request);

        $totalCount = $qb->getQuery()->getSingleScalarResult();

        $qb->select('l')
            ->orderBy('l.id', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($page*$perPage);

        $items = $qb->getQuery()->getResult();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $item){
            if (true === $authorizationChecker->isGranted('VIEW', $item)){
                $itemlist[] = $item;
            }
        }

        return ['total_count'=> (int)$totalCount, 'total_items' => (int)$totalItems, 'list'=>$itemlist];
    }

    /**
     * @Rest\Get("/label/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getLabelAction(\AppBundle\Entity\Label $label)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $label)){
            return $label;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$label->getId().' Not Found');
        }
    }

    /**
     * @Rest\Post("/label")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("label", converter="fos_rest.request_body")
     */
    public function createLabelAction(\AppBundle\Entity\Label $label)
    {
        if($this->get('security.authorization_checker')->isGranted('CREATE', $label)){
            $em = $this->getDoctrine()->getManager();
            $em->persist($label);
            $em->flush();
            $this->updateAclByRoles($label, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
            return $label;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$label->getId().' Not Found');
        }
    }

    /**
     * @Rest\Put("/label/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("label", converter="fos_rest.request_body")
     */
    public function updateLabelAction(\AppBundle\Entity\Label $label)
    {
        if($this->get('security.authorization_checker')->isGranted('EDIT', $label)){
            $em = $this->getDoctrine()->getManager();
            $em->merge($label);
            $em->flush();
            return $label;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$label->getId().' Not Found');
        }
    }

    /**
     * @Rest\Delete("/label/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function deleteLabelAction(\AppBundle\Entity\Label $label)
    {
        if($this->get('security.authorization_checker')->isGranted('DELETE', $label)){
            $em = $this->getDoctrine()->getManager();
            $em->remove($label);
            $em->flush();
            return $label;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$label->getId().' Not Found');
        }
    }

    /**
     * @Rest\Get("/label_on_site_printer")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function listLabelOnSitePrinterAction(Request $request)
    {
        $page = (int)$request->query->get('page') - 1;
        $perPage =(int)$request->query->get('per_page');
        $qb = $this->getDoctrine()->getManager()->createQueryBuilder()
            ->select('COUNT(losp.id)')
            ->from('AppBundle:LabelOnSitePrinter', 'losp');

        $totalItems = $qb->getQuery()->getSingleScalarResult();

        Utilities::setupSearchableEntityQueryBuild($qb, $request);

        $totalCount = $qb->getQuery()->getSingleScalarResult();

        $qb->select('losp')
            ->orderBy('losp.id', 'DESC')
            ->setMaxResults($perPage)
            ->setFirstResult($page*$perPage);

        $items = $qb->getQuery()->getResult();

        $itemlist = array();
        $authorizationChecker = $this->get('security.authorization_checker');
        foreach($items as $item){
            if (true === $authorizationChecker->isGranted('VIEW', $item)){
                $itemlist[] = $item;
            }
        }

        return ['total_count'=> (int)$totalCount, 'total_items' => (int)$totalItems, 'list'=>$itemlist];
    }

    /**
     * @Rest\Get("/label_on_site_printer/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function getLabelOnSitePrinterAction(\AppBundle\Entity\LabelOnSitePrinter $labelOnSitePrinter)
    {
        if($this->get('security.authorization_checker')->isGranted('VIEW', $labelOnSitePrinter)){
            return $labelOnSitePrinter;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$labelOnSitePrinter->getId().' Not Found');
        }
    }

    /**
     * @Rest\Post("/label_on_site_printer")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     * @ParamConverter("labelOnSitePrinter", converter="fos_rest.request_body")
     */
    public function createLabelOnSitePrinterAction(\AppBundle\Entity\LabelOnSitePrinter $labelOnSitePrinter)
    {
        if($this->get('security.authorization_checker')->isGranted('CREATE', $labelOnSitePrinter)){
            $em = $this->getDoctrine()->getManager();
            $em->persist($labelOnSitePrinter);
            $em->flush();
            $this->updateAclByRoles($labelOnSitePrinter, ['ROLE_USER'=>'view', 'ROLE_ADMIN'=>'operator']);
            return $labelOnSitePrinter;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$labelOnSitePrinter->getId().' Not Found');
        }

    }

    /**
     * @Rest\Delete("/label_on_site_printer/{id}")
     * @Rest\View(template=":default:index.html.twig",serializerEnableMaxDepthChecks=true, serializerGroups={"Default"})
     */
    public function deleteLabelOnSitePrinterAction(\AppBundle\Entity\LabelOnSitePrinter $labelOnSitePrinter)
    {
        if($this->get('security.authorization_checker')->isGranted('DELETE', $labelOnSitePrinter)){
            $em = $this->getDoctrine()->getManager();
            $em->remove($labelOnSitePrinter);
            $em->flush();
            return $labelOnSitePrinter;
        }else{
            throw $this->createNotFoundException('MenuItem #'.$labelOnSitePrinter->getId().' Not Found');
        }
    }

}
//nohup php app/console thruway:process start &
//curl -v -H "Accept: application/json" -H "Content-type: application/json" -X POST -d '{"name":"DFW"}' http://localhost/~belac/stepthrough/app_dev.php/office
//curl -v -H "Accept: application/json" -H "PHP_AUTH_USER: admintest" -H "PHP_AUTH_PW: password" http://localhost/~belac/stepthrough/app_dev.php/office