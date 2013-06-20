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

require_once(_PS_MODULE_DIR_.'treecategories/treecategories.php');

define('TREE_BASE_FOLDER', dirname(__FILE__).'/');
define('TREE_TEMPLATES_BASE_FOLDER', TREE_BASE_FOLDER.'templates/');

function unicity_key_compare($a, $b, $unicity_key_name = 'id_category') {
    if ($a[$unicity_key_name] == $b[$unicity_key_name]) {
        return 0;
    }
    return ($a[$unicity_key_name] < $b[$unicity_key_name]) ? -1 : 1;
}

class AdminTreeProductsController extends AdminProductsControllerCore{

    protected $actions_available = array('edit', 'delete', 'duplicate'); // We don't want view action
    
    public function getAvailableActions(){
        return $this->actions_available;
    }
    
    public function getPositionIdentifier(){
        return $this->position_identifier;
    }

	protected function l($string, $class = 'AdminProducts', $addslashes = false, $htmlentities = true)
	{
        // Override the translation function so that we use translations stored for AdminCategories
        return parent::l($string, $class, $addslashes, $htmlentities);
	}
	
}

class AdminTreeCategoriesController extends AdminCategoriesControllerCore {

    public $tree_tpl = 'categories.tpl';
    public $tree_templates_base_folder = TREE_TEMPLATES_BASE_FOLDER;
    public $is_multishop;

    // Returns the parent category_id, without looking into the request
    private function getIdParent(){
		$count_categories_without_parent = count(Category::getCategoriesWithoutParent());
		$this->is_multishop = Shop::isFeatureActive();
		$top_category = Category::getTopCategory();

		if (!$this->is_multishop && $count_categories_without_parent > 1)
			$id_parent = $top_category->id;
		else if ($this->is_multishop && $count_categories_without_parent == 1)
			$id_parent = Configuration::get('PS_HOME_CATEGORY');
		else if ($this->is_multishop && $count_categories_without_parent > 1 && Shop::getContext() != Shop::CONTEXT_SHOP)
			$id_parent = $top_category->id;
		else
			$id_parent = $this->context->shop->id_category;

        return $id_parent;
    }

    // Generates the tree with arrays
    private function getTree($order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        $selectedCategory = isset($_GET['id_category']) ? $_GET['id_category'] : null;

        // Set variables needed for the sql query

        $select_shop = $sql_table = $filter_shop = $lang_join = $join_shop = $where_shop = $having_clause = null;

        $id_lang = $this->context->language->id;
        parent::getList($id_lang);

        /* Settings from AdminCategoriesController renderList */

        $id_parent = $this->getIdParent();

		// we add restriction for shop
        $select_shop = $join_shop = $where_shop = '';
		if ($this->shopLinkType)
		{
			$select_shop = ', shop.name as shop_name ';
			$join_shop = ' LEFT JOIN '._DB_PREFIX_.$this->shopLinkType.' shop
							ON a.id_'.$this->shopLinkType.' = shop.id_'.$this->shopLinkType;
			$where_shop = Shop::addSqlRestriction($this->shopShareDatas, 'a', $this->shopLinkType);
		}

		if ($this->multishop_context && Shop::isTableAssociated($this->table) && !empty($this->className))
		{
			if (Shop::getContext() != Shop::CONTEXT_ALL || !$this->context->employee->isSuperAdmin())
			{
				$test_join = !preg_match('#`?'.preg_quote(_DB_PREFIX_.$this->table.'_shop').'`? *sa#', $this->_join);
				if (Shop::isFeatureActive() && $test_join && Shop::isTableAssociated($this->table))
				{
					$this->_where .= ' AND a.'.$this->identifier.' IN (
						SELECT sa.'.$this->identifier.'
						FROM `'._DB_PREFIX_.$this->table.'_shop` sa
						WHERE sa.id_shop IN ('.implode(', ', Shop::getContextListShopID()).')
					)';
				}
			}
		}

        /* End of stolen block */

        // Tweak the sql query to ignore the filter (we want to select all cagetHelpertegories, not just one
        // No longer uses a limit
        // Tweaked where condition

        //$order_by = $this->table.'_shop.'.$this->_orderBy;
        $order_by = 'sa.'.$this->_orderBy;
		$sql_table = $this->table;
		$lang_join = '';
		if ($this->lang){
			$lang_join = 'LEFT JOIN `'._DB_PREFIX_.$this->table.'_lang` b ON (b.`'.$this->identifier.'` = a.`'.$this->identifier.'`';
			$lang_join .= ' AND b.`id_lang` = '.(int)$id_lang;
			if ($id_lang_shop)
				if (Shop::getContext() == Shop::CONTEXT_SHOP)
					$lang_join .= ' AND b.`id_shop`='.(int)$id_lang_shop;
				else
					$lang_join .= ' AND b.`id_shop` IN ('.implode(',', array_map('intval', Shop::getContextListShopID())).')';
			$lang_join .= ')';
		}

		$sql = 'SELECT SQL_CALC_FOUND_ROWS
			'.($this->_tmpTableFilter ? ' * FROM (SELECT ' : '').'
			'.($this->lang ? 'b.*, ' : '').'a.*'.(isset($this->_select) ? ', '.$this->_select.' ' : '').$select_shop.'
			FROM `'._DB_PREFIX_.$sql_table.'` a
			'.$filter_shop.'
			'.$lang_join.'
			'.(isset($this->_join) ? $this->_join.' ' : '').'
			'.$join_shop.'
			WHERE 1 '.(isset($this->_where) ? $this->_where.' ' : '').($this->deleted ? 'AND a.`deleted` = 0 ' : '').
			$where_shop.'
			'.(isset($this->_group) ? $this->_group.' ' : '').'
			'.$having_clause.'
			ORDER BY '.(($order_by == $this->identifier) ? 'a.' : '').pSQL($order_by).' '.pSQL($order_way).
			($this->_tmpTableFilter ? ') tmpTable WHERE 1'.$this->_tmpTableFilter : '');
			    
		$categories = Db::getInstance()->executeS($sql);

        // Mark the currently selected element
        $currentlySelected = $this->search($categories,'id_category',$selectedCategory);
        if(!is_null($currentlySelected)){
            $completedCat = $currentlySelected;
            $completedCat['treeClass'] = 'openNode';
            $categories[array_search($currentlySelected, $categories)] = $completedCat;
        }

        // Removes unwanted categories (cannot be done in request, we want all the categories that are related to parent categorie 
        // Building tree
        $tree = array();

        foreach($categories as $cat){
            $parent = $cat;
            $branch = $cat;
        
            // Recursively gets the highest category tree level  
    
            while($parent['is_root_category'] != 1){
                $upper = $this->search($categories,'id_category',$parent['id_parent']);
                // Manage top levels that are referencing themselves as parent
                if($upper == $parent || $upper == null)
                    break;
                $parent = $upper;
                $upper['children'] = $branch;
                $branch = $upper;
            }

            // Removing category if requirements don't fit
            if($parent['id_category'] != $id_parent)
               unset($categories[array_search($cat,$categories)]);
            else{
                $tree = $this->insertTreePath($tree, $branch, 'id_category', 'children');
            }
        }

        return $tree;
    }

    // Insert a tree path (like a breadcrumb) into the tree
    private function insertTreePath($tree, $path, $unicity_key_name, $children_key_name, $is_root = True){
        $current = $path;
        $existing = $this->search($tree, $unicity_key_name, $current[$unicity_key_name]);
        $children = null;

        if($existing == null){
            $existing = $current;
            if(array_key_exists($children_key_name,$existing))
		        unset($existing[$children_key_name]);
                $existing[$children_key_name] = array();
            array_push($tree,$existing);
            
        }

        // Means the path is not finished yet
        if(array_key_exists($children_key_name,$current))
            $children = $current[$children_key_name];
        else{
            return $tree;
        }    

        $subChildren = $this->insertTreePath($existing[$children_key_name], $children, $unicity_key_name, $children_key_name, $is_root = False);
        uasort($subChildren, 'unicity_key_compare');
        $tree[array_search($existing, $tree)][$children_key_name] = $subChildren;
        return $tree;
    }

    // Returns the first array element that has a key equal to the specified value
    public function search($array, $key, $value)
    {
        foreach($array as $element){   
            if (isset($element[$key]) && $element[$key] == $value)
                    return $element;
        }
        return null;
    }

    // Override the translation function so that we use translations stored for AdminCategories
	protected function l($string, $class = 'AdminCategories', $addslashes = false, $htmlentities = true)
	{
        return parent::l($string, $class, $addslashes, $htmlentities);
	}

    public function renderList()
	{
		parent::renderList();
		
        // Reinstanciates helper to set the module parameter, otherwise the template won't be overriden.
        // With module set, looks for templates in _PS_MODULE_DIR.'treecategories/views/templates/admin/_configure/tree_categories/helpers/list/'
        // Base templates can be found here: _PS_ADMIN_DIR.'themes/default/template/helpers/list/'
        
        // Set post var id_category for product filtering
    
        if(!Tools::isSubmit('id_category')){
            $_POST['id_category'] = $this->getIdParent();
        }
        // automatically manages the id_category parameter, calls the getList method to set $productsController->_list 
        
        $productsController = new AdminTreeProductsController();
        $productsController->getList($this->context->language->id);          

        // get the AdminProducts class name, for link generation
        
        $productsCtrl = str_replace('ControllerCore','',get_parent_class($productsController));

        // get the Helper and replace some parameters forlik generation
        
	    $helper = new HelperList();        
		$this->setHelperDisplay($helper);
        $helper->module = new Treecategories();
        $helper->identifier = $productsController->identifier;	
        $helper->position_identifier = $productsController->getPositionIdentifier();
		$helper->actions = $productsController->getAvailableActions();
		$helper->table = $productsController->table;
		$helper->className = get_parent_class($productsController);
		$helper->currentIndex = 'index.php?controller='.$productsCtrl;
		$helper->token = Tools::getAdminTokenLite($productsCtrl);
		       
        // Set additional properties, includes needed javascripts and insert the html tree on the page
        
        $helper->tpl_vars['tree'] = $this->renderTree();
        $helper->tpl_vars['tree_base_folder'] = __PS_BASE_URI__.str_replace(_PS_ROOT_DIR_.'/','',TREE_BASE_FOLDER);
        $this->addJS(__PS_BASE_URI__.str_replace(_PS_ROOT_DIR_,'',_PS_MODULE_DIR_).'treecategories/jstree/jquery.jstree.js');
        $this->addJS(__PS_BASE_URI__.str_replace(_PS_ROOT_DIR_,'',_PS_MODULE_DIR_).'treecategories/jstree/category.tree.js');
        $this->addCSS(__PS_BASE_URI__.str_replace(_PS_ROOT_DIR_,'',_PS_MODULE_DIR_).'treecategories/controllers/admin/styles/treecategories.css');
        
		$list = $helper->generateList($productsController->_list, $productsController->fields_list);

		return $list;
	}

    // Recursively renders the tree with <ul><li><a> ...
    // See treecategories/controllers/admin/templates/categories.tpl for more details
    private function renderNodes($nodes, $level){
        $template = $this->context->smarty->createTemplate($this->tree_templates_base_folder.$this->tree_tpl, $this->context->smarty); 

        foreach($nodes as $node){
            if(array_key_exists('children',$node)){
                $renderedChildrens = $this->renderNodes($node['children'], $level + 1);
                $completedNode = $node;
                $completedNode['subparts'] = $renderedChildrens;
                $completedNode['link'] = $this->context->link->getAdminLink('AdminTreeCategories', false).'&id_category='.(int)$completedNode['id_category'].'&viewcategory'.'&token='.$this->token;
                $nodes[array_search($node,$nodes)] = $completedNode;
            }
        }
        $template->assign(array(
			    'treeLevel' => $level,
                'nodes' => $nodes,
	    ));
        $content = $template->fetch();
        return $content;
    }

    // Generate the tree and renders it
    public function renderTree(){
        $tree = $this->getTree();
        return $this->renderNodes($tree, 0);    

    }
}

?>
