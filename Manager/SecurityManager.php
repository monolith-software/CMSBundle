<?php

declare(strict_types=1);

namespace Monolith\Bundle\CMSBundle\Manager;

use Doctrine\ORM\Tools\SchemaValidator;
use Monolith\Bundle\CMSBundle\Entity\Folder;
use Monolith\Bundle\CMSBundle\Entity\Node;
use Monolith\Bundle\CMSBundle\Entity\Permission;
use Monolith\Bundle\CMSBundle\Entity\Region;
use Monolith\Bundle\CMSBundle\Entity\UserGroup;
use Monolith\Bundle\CMSBundle\Model\UserModel;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Yaml\Yaml;

class SecurityManager
{
    use ContainerAwareTrait;

    /** @var \Doctrine\ORM\EntityManager $em */
    protected $em;

    /** @var array  */
    protected $usersPermissionsCache = [];

    /** @var array  */
    protected $usersGroupsCache      = [];

    /**
     * SecurityManager constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->em        = $container->get('doctrine.orm.entity_manager');
    }

    /**
     * Запрос может быть 3-х видов:
     *
     * 1. cms:admin.system
     * 2. group=admin
     * 3. ROLE_ADMIN
     *
     * @param string $slug
     *
     * @return bool
     */
    public function isGranted(string $slug): bool
    {
        /** @var UserModel $user */
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $securityChecker = $this->container->get('security.authorization_checker');

        if ($user instanceof UserModel and $securityChecker->isGranted('ROLE_SUPER_ADMIN', $user)) {
            return true;
        }

        if (isset($this->getUserPermissions($user)[$slug])) {
            return true;
        }

        return false;
    }

    /**
     * @param UserModel|string $user
     *
     * @return array
     */
    public function getUserPermissions($user): array
    {
        if ($user instanceof UserModel) {
            if (!isset($this->usersPermissionsCache[$user->getId()])) {
                $this->createPermissionsMapForUser($user);
            }

            return $this->usersPermissionsCache[$user->getId()];
        }

        return [];
    }

    /**
     * @param UserModel $user
     *
     * @todo !!! кеширование
     */
    protected function createPermissionsMapForUser(UserModel $user): void
    {
        $permissions = [];

        foreach ($user->getRoles() as $role) {
            $permissions[$role] = true;
        }

        /** @var UserGroup[] $groups */
        $groups = $user->getGroups();

        foreach ($groups as $group) {
            $permissions['group='.$group->getName()] = true;
            foreach ($group->getPermissions() as $permission) {
                $permissions[(string) $permission] = true;
            }
        }

        $this->usersPermissionsCache[$user->getId()] = $permissions;
    }
    
    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function warmupDatabase(): void
    {
        $validator = new SchemaValidator($this->em);
        if (false === $validator->schemaInSyncWithMetadata()) {
            return;
        }

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->em;

        foreach ($this->container->getParameter('kernel.bundles') as $bundleName => $bundleClass) {
            $reflector = new \ReflectionClass($bundleClass);
            $permissionsConfig = dirname($reflector->getFileName()).'/Resources/config/permissions.yml';

            if (file_exists($permissionsConfig)) {
                /** @var \Symfony\Component\HttpKernel\Bundle\Bundle $bundle */
                $bundle = new $bundleClass();

                if (empty($bundle->getContainerExtension())) {
                    continue;
                }

                $permissionsConfig = Yaml::parse(file_get_contents($permissionsConfig));

                // Создание массива прав, где в качестве ключей используется 'action'.
                $permissions = [];
                foreach ($em->getRepository(Permission::class)->findBy(['bundle' => $bundle->getContainerExtension()->getAlias()]) as $permission) {
                    $permissions[$permission->getAction()] = $permission;
                }

                if (!empty($permissionsConfig)) {
                    $pos = 0;

                    foreach ($permissionsConfig as $action => $data) {
                        $roles = [];
                        if (is_array($data)) {
                            if (isset($data['default'])) {
                                $default = (bool) $data['default'];
                            }

                            if (isset($data['roles'])) {
                                if (is_array($data['roles'])) {
                                    $roles = $data['roles'];
                                } else {
                                    $roles = array($data['roles']);
                                }
                            }
                        } else {
                            $default = (bool) $data;
                        }

                        if (isset($permissions[$action])) {
                            $permission = $permissions[$action];
                            $permission
                                ->setDefaultValue($default)
                                ->setPosition($pos)
                                ->setRoles($roles)
                            ;

                            unset($permissions[$action]);
                        } else {
                            $permission = new Permission();
                            $permission
                                ->setBundle($bundle->getContainerExtension()->getAlias())
                                ->setAction($action)
                                ->setDefaultValue($default)
                                ->setRoles($roles)
                                ->setPosition($pos)
                            ;

                            $errors = $this->container->get('validator')->validate($permission);

                            if (count($errors) > 0) {
                                $em->detach($permission);
                            } else {
                                $em->persist($permission);
                            }
                        }

                        $pos++;
                    }

                    foreach ($permissions as $permission) {
                        $em->remove($permission);
                    }

                    $em->flush();
                }
            } // _end file_exists($permissionsConfig)
        }
    }

    /**
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function checkDefaultUserGroups(): void
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');
        $t = $this->container->get('translator');

        if (empty($em->getRepository(UserGroup::class)->findOneBy(['name' => 'guests']))) {
            $userGroup = new UserGroup('guests');
            $userGroup
                ->setTitle($t->trans('Guests'))
                ->setPosition(0)
                ->setIsDefaultFoldersGrantedWrite(false)
                ->setIsDefaultNodesGrantedWrite(false)
            ;

            $em->persist($userGroup);

            $this->addDefaultPermissionsGroupForAllFolders($userGroup);
            $this->addDefaultPermissionsGroupForAllNodes($userGroup);
            $this->addDefaultPermissionsGroupForAllRegions($userGroup);

            $em->flush($userGroup);
        }

        if (empty($em->getRepository(UserGroup::class)->findOneBy(['name' => 'admin']))) {
            $userGroup = new UserGroup('admin');
            $userGroup
                ->setTitle($t->trans('Administrators'))
                ->setPosition(1)
            ;

            $em->persist($userGroup);

            $this->addDefaultPermissionsGroupForAllFolders($userGroup);
            $this->addDefaultPermissionsGroupForAllNodes($userGroup);
            $this->addDefaultPermissionsGroupForAllRegions($userGroup);

            $em->flush($userGroup);
        }

        if (empty($em->getRepository(UserGroup::class)->findOneBy(['name' => 'user']))) {
            $userGroup = new UserGroup('user');
            $userGroup
                ->setTitle($t->trans('Authorized users'))
                ->setPosition(2)
                ->setIsDefaultFoldersGrantedWrite(false)
                ->setIsDefaultNodesGrantedWrite(false)
            ;
            $em->persist($userGroup);

            $this->addDefaultPermissionsGroupForAllFolders($userGroup);
            $this->addDefaultPermissionsGroupForAllNodes($userGroup);
            $this->addDefaultPermissionsGroupForAllRegions($userGroup);

            $em->flush($userGroup);
        }
    }

    /**
     * @param UserGroup $userGroup
     */
    public function addDefaultPermissionsGroupForAllFolders(UserGroup $userGroup): void
    {
        foreach ($this->em->getRepository(Folder::class)->findAll() as $folder) {
            if ($userGroup->isDefaultFoldersGrantedRead()) {
                $folder->addGroupGrantedRead($userGroup);
            }

            if ($userGroup->isDefaultFoldersGrantedWrite()) {
                $folder->addGroupGrantedWrite($userGroup);
            }

            $this->em->flush($folder);
        }

        $this->container->get('cms.cache')->invalidateTags(['node', 'folder']);
    }

    /**
     * @param UserGroup $userGroup
     */
    public function addDefaultPermissionsGroupForAllNodes(UserGroup $userGroup): void
    {
        foreach ($this->em->getRepository(Node::class)->findAll() as $node) {
            if ($userGroup->isDefaultNodesGrantedRead()) {
                $node->addGroupGrantedRead($userGroup);
            }

            if ($userGroup->isDefaultNodesGrantedWrite()) {
                $node->addGroupGrantedWrite($userGroup);
            }

            $this->em->flush($node);
        }

        $this->container->get('cms.cache')->invalidateTags(['node', 'folder']);
    }

    /**
     * @param UserGroup $userGroup
     */
    public function addDefaultPermissionsGroupForAllRegions(UserGroup $userGroup): void
    {
        foreach ($this->em->getRepository(Region::class)->findAll() as $region) {
            if ($userGroup->isDefaultNodesGrantedRead()) {
                $region->addGroupGrantedRead($userGroup);
            }

            if ($userGroup->isDefaultNodesGrantedWrite()) {
                $region->addGroupGrantedWrite($userGroup);
            }

            $this->em->flush($region);
        }

        $this->container->get('cms.cache')->invalidateTags(['node', 'folder', 'region']);
    }

    /**
     * @param array  $folders
     * @param string $permission
     *
     * @return bool
     */
    public function checkForFoldersRouterData(array $folders, string $permission = 'read'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userGroups = $this->getUserGroups();

        foreach ($folders as $folder) {
            if (!isset($folder['permissions'][$permission])) {
                return false;
            }

            if (array_diff_key($userGroups, $folder['permissions'][$permission])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param Folder $folder
     *
     * @return bool
     */
    public function checkForFolderAccess(Folder $folder, string $permission = 'read'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userGroups = $this->getUserGroups();

        foreach ($folder->getPermissionsCache($permission) as $userGroupId => $_userGroupName) {
            if (isset($userGroups[$userGroupId])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Node   $node
     * @param string $permission
     *
     * @return bool
     */
    public function checkForNodeAccess(Node $node, string $permission = 'read'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userGroups = $this->getUserGroups();

        foreach ($node->getPermissionsCache($permission) as $userGroupId => $userGroupName) {
            if (in_array($userGroupName, $userGroups)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param Region $region
     * @param string $permission
     *
     * @return bool
     */
    public function checkForRegionAccess(Region $region, string $permission = 'read'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $userGroups = $this->getUserGroups();

        foreach ($region->getPermissionsCache($permission) as $userGroupId => $userGroupName) {
            if (in_array($userGroupName, $userGroups)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array|bool
     */
    public function getUserGroups()
    {
        if (!empty($this->usersGroupsCache)) {
            return $this->usersGroupsCache;
        }

        /** @var UserModel $user */
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        /** @var хранить в кеше данные о гостевой группе $guestGroup */
        $guestGroup = $em->getRepository(UserGroup::class)->findOneBy(['name' => 'guests']);

        if ($guestGroup) {
            $userGroups = [
                $guestGroup->getId() => $guestGroup->getName(),
            ];
        } else {
            $userGroups = [];
        }

        if ($user instanceof UserModel) {
            $userGroups = $user->getGroupNames();
        }

        return $this->usersGroupsCache = $userGroups;
    }

    /**
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        /** @var UserModel $user */
        $user = $this->container->get('security.token_storage')->getToken()->getUser();

        $securityChecker = $this->container->get('security.authorization_checker');

        if ($user instanceof UserModel and $securityChecker->isGranted('ROLE_SUPER_ADMIN', $user)) {
            return true;
        }

        return false;
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateAllFoldersByDefaults(): int
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $defaultsGrantedRead  = $em->getRepository(UserGroup::class)->findBy(['is_default_folders_granted_read' => 1]);
        $defaultsGrantedWrite = $em->getRepository(UserGroup::class)->findBy(['is_default_folders_granted_write' => 1]);

        $cnt = 0;
        foreach ($em->getRepository(Folder::class)->findAll() as $folder) {
            $folder->clearGroupGrantedRead();
            $folder->clearGroupGrantedWrite();

            foreach ($defaultsGrantedRead as $userGroup) {
                $folder->addGroupGrantedRead($userGroup);
            }

            foreach ($defaultsGrantedWrite as $userGroup) {
                $folder->addGroupGrantedWrite($userGroup);
            }

            $em->flush($folder);
            $cnt++;
        }

        $this->container->get('cms.cache')->invalidateTags(['node', 'folder']);

        return $cnt;
    }

    /**
     * @return int
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function updateAllNodesByDefaults(): int
    {
        /** @var \Doctrine\ORM\EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        $defaultsGrantedRead  = $em->getRepository(UserGroup::class)->findBy(['is_default_nodes_granted_read' => 1]);
        $defaultsGrantedWrite = $em->getRepository(UserGroup::class)->findBy(['is_default_nodes_granted_write' => 1]);

        $cnt = 0;
        foreach ($em->getRepository(Node::class)->findAll() as $node) {
            $node->clearGroupGrantedRead();
            $node->clearGroupGrantedWrite();

            foreach ($defaultsGrantedRead as $userGroup) {
                $node->addGroupGrantedRead($userGroup);
            }

            foreach ($defaultsGrantedWrite as $userGroup) {
                $node->addGroupGrantedWrite($userGroup);
            }

            $em->flush($node);
            $cnt++;
        }

        $this->container->get('cms.cache')->invalidateTags(['node', 'folder']);

        return $cnt;
    }
}
