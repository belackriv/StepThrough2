<?php
namespace AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use AppBundle\Entity\Label;

class LoadLabelData extends AbstractFixture implements ContainerAwareInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }


    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $createdEntities = [];
        $tidLabel = $manager->getRepository('AppBundle:Label')->findOneBy(['name'=>'TravelerId Label']);
        if(!$tidLabel){
            $tidLabel = new Label();
            $tidLabel->setName('TravelerId Label');
            $tidLabel->setDescription('ZPL TID Label');
            $tidLabel->setTemplate('
                ^XA
                ^FO50,50^BY3
                ^BCN,100,Y,N,N
                ^FD{{tid}}
                ^XZ
            ');
            $manager->persist($tidLabel);
            $createdEntities['tidLabel'] = true;
        }
        $manager->flush();

        //$this->addReference('dfwOffice', $dfwOffice);

        $aclProvider = $this->container->get('security.acl.provider');
        $devRoleSecurityIdentity = new RoleSecurityIdentity('ROLE_DEV');
        $adminRoleSecurityIdentity = new RoleSecurityIdentity('ROLE_ADMIN');
        $leadRoleSecurityIdentity = new RoleSecurityIdentity('ROLE_LEAD');
        $userRoleSecurityIdentity = new RoleSecurityIdentity('ROLE_USER');

        if(isset($createdEntities['tidLabel']) and $createdEntities['tidLabel']){
            $objectIdentity = ObjectIdentity::fromDomainObject($tidLabel);
            $acl = $aclProvider->createAcl($objectIdentity);
            $acl->insertObjectAce($userRoleSecurityIdentity, MaskBuilder::MASK_VIEW);
            $acl->insertObjectAce($adminRoleSecurityIdentity, MaskBuilder::MASK_OPERATOR);
            $aclProvider->updateAcl($acl);
        }

    }

}