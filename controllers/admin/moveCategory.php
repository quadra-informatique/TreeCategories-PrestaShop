<?php
/**
 * ---------------------------------------------------------------------------------
 * 
 * 1997-2013 Quadra Informatique
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to modules@quadra-informatique.fr so we can send you a copy immediately.
 *
 * @author Quadra Informatique <modules@quadra-informatique.fr>
 * @copyright 1997-2013 Quadra Informatique
 * @license http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * ---------------------------------------------------------------------------------
*/

require_once('../../../../config/defines.inc.php');
require_once(_PS_ROOT_DIR_.'/config/config.inc.php');
require_once(_PS_CLASS_DIR_.'Tools.php');
require_once(_PS_CLASS_DIR_.'Category.php');

$_errors = array();
$parentId = isset($_GET['parent']) ? $_GET['parent'] : null;
$targetId = isset($_GET['target']) ? $_GET['target'] : null;

$exists = false;

if(is_null($parentId) or is_null($targetId))
    $_errors[] = Tools::displayError('Failed, one or more parameters are missing.');
else
    $exists = Category::categoryExists($parentId) and Category::categoryExists($targetId);

if($exists){
    $cat = new Category($targetId);
    $cat->id_parent = $parentId;
    $cat->save();
}
else
    $_errors[] = Tools::displayError('Failed, category does not exist.');

echo json_encode($_errors);
