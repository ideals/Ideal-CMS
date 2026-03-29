<?php

/**
 * Ideal CMS (http://idealcms.ru/)
 *
 * @link      http://github.com/ideals/idealcms репозиторий исходного кода
 * @copyright Copyright (c) 2012-2018 Ideal CMS (http://idealcms.ru/)
 * @license   http://idealcms.ru/license.html LGPL v3
 */

namespace Ideal\Structure\Service\Acl;

use Ideal\Structure\Acl\Admin\Model as StructureAclModel;

/**
 * Реакция на действия со страницы "Права доступа"
 *
 */
class AjaxController extends \Ideal\Core\AjaxController
{
    /** @var object Ideal\Structure\Acl\Admin\Model */
    protected StructureAclModel $structureAclModel;

    public function __construct()
    {
        $this->structureAclModel = new StructureAclModel();
    }

    /**
     * Получение списка первого уровня для управления правами
     */
    public function mainUserGroupPermissionAction()
    {
        $permission = $this->structureAclModel->getMainUserGroupPermission();
        return json_encode($permission, JSON_FORCE_OBJECT);
    }

    /**
     * Получение списка дочерних пунктов для управления правами
     */
    public function showChildrenAction()
    {
        $permission = $this->structureAclModel->getChildrenPermission();
        return json_encode($permission, JSON_FORCE_OBJECT);
    }

    /**
     * Занесение в базу изменённого правила для соответствующего пункта
     */
    public function changePermissionAction(): void
    {
        $this->structureAclModel->changePermission();
    }

    /**
     * {@inheritdoc}
     * @return array<string, string>
     */
    public function getHttpHeaders(): array
    {
        return [
            'Content-type' => 'Content-type: application/json',
        ];
    }
}
