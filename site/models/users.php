<?php
/*------------------------------------------------------------------------

# TZ Portfolio Extension

# ------------------------------------------------------------------------

# author    DuongTVTemPlaza

# copyright Copyright (C) 2012 templaza.com. All Rights Reserved.

# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL

# Websites: http://www.templaza.com

# Technical Support:  Forum - http://templaza.com/Forum

-------------------------------------------------------------------------*/

// no direct access
defined('_JEXEC') or die();
jimport('joomla.application.component.model');
jimport('joomla.html.pagination');
 
class TZ_PortfolioModelUsers extends JModelLegacy
{
    protected $pagNav   = null;

    function populateState(){
        $pk = JRequest::getInt('created_by');
        $this -> setState('users.id',$pk);
        $offset = JRequest::getUInt('limitstart',0);
		$this->setState('offset', $offset);
        $app    = &JFactory::getApplication('site');
        $params = $app -> getParams();
        $limit  = $app->getUserStateFromRequest('com_tz_portfolio.users.limit','limit',10);
        $this -> setState('params',$params);
        $this -> setState('limit',$limit);
        $this -> setState('users.catid',null);
        $this -> setState('char',JRequest::getString('char',null));

    }

    function getUsers(){
        $app    = &JFactory::getApplication('site');
        $limit  = $app->getUserStateFromRequest('com_tz_portfolio.users.limit','limit',10);

        $params = $this -> getState('params');

        if($params -> get('tz_article_limit')){
            $limit  = $params -> get('tz_article_limit');
        }

        $params -> set('access-view',true);

        $this->setState('params', $params);

        $where  = null;
        if($char   = $this -> getState('char')){
            $where  = ' AND ASCII(SUBSTR(LOWER(c.title),1,1)) = ASCII("'.mb_strtolower($char).'")';
        }

        $query  = 'SELECT COUNT(*) FROM #__content AS c'
                  .' WHERE c.created_by='.$this -> getState('users.id')
                  .$where;
        $db     = &JFactory::getDbo();
        $db -> setQuery($query);
        $total  = $db -> loadResult();

        $this -> pagNav = new JPagination($total,$this -> getState('offset'),$limit);

        switch ($params -> get('orderby_sec')){
            default:
                $orderby    = 'id DESC';
                break;
            case 'rdate':
                $orderby    = 'created DESC';
                break;
            case 'date':
                $orderby    = 'created ASC';
                break;
            case 'alpha':
                $orderby    = 'title ASC';
                break;
            case 'ralpha':
                $orderby    = 'title DESC';
                break;
            case 'author':
                $orderby    = 'create_by ASC';
                break;
            case 'rauthor':
                $orderby    = 'create_by DESC';
                break;
            case 'hits':
                $orderby    = 'hits DESC';
                break;
            case 'rhits':
                $orderby    = 'hits ASC';
                break;
            case 'order':
                $orderby    = 'ordering ASC';
                break;
        }

        $query  = 'SELECT c.*,cc.title AS category_title,cc.parent_id,'
            .' CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(":", c.id, c.alias) ELSE c.id END as slug,'
            .' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug,'
            .' CASE WHEN CHAR_LENGTH(c.fulltext) THEN c.fulltext ELSE null END as readmore'
            .' FROM #__content AS c'
            .' LEFT JOIN #__categories AS cc ON cc.id = c.catid'
            .' WHERE c.state=1 AND c.created_by='.$this -> getState('users.id')
            .$where
            .' ORDER BY '.$orderby;


        $db -> setQuery($query,$this -> pagNav -> limitstart,$this -> pagNav -> limit);

        if(!$db -> query()){
            var_dump($db -> getErrorMsg());
            return false;
        }
        if($rows   = $db -> loadObjectList()){
            return $rows;
        }
        return '';
    }

    function getPagination(){
        if($this -> pagNav)
            return $this -> pagNav;
        return '';
    }

    function getFindType($_cid=null)
	{
        $cid    = $this -> getState('users.catid');
		$app		= JFactory::getApplication();
		$menus		= $app->getMenu('site');
        $cid        =   intval($cid);
        if($_cid){
            $cid    = intval($_cid);
        }

        $component	= JComponentHelper::getComponent('com_tz_portfolio');
		$items		= $menus->getItems('component_id', $component->id);

        foreach ($items as $item)
        {
            if (isset($item->query) && isset($item->query['view'])) {
                $view = $item->query['view'];

                if (isset($item->query['id'])) {
                    if ($item->query['id'] == $cid) {
                        return 0;
                    }
                } else {

                    $catids = $item->params->get('tz_catid');
                    if ($view == 'portfolio' && $catids) {
                        if (is_array($catids)) {
                            for ($i = 0; $i < count($catids); $i++) {
                                if ($catids[$i] == 0 || $catids[$i] == $cid) {
                                    return 1;
                                }
                            }
                        } else {
                            if ($catids == $cid) {
                                return 1;
                            }
                        }
                    }
                }
            }
        }

		return 0;
	}

    function getFindItemId($_cid=null)
	{
        $cid    = $this -> getState('users.catid');
		$app		= JFactory::getApplication();
		$menus		= $app->getMenu('site');
        $active     = $menus->getActive();
        $cid        =   intval($cid);
        if($_cid){
            $cid    = intval($_cid);
        }

        $component	= JComponentHelper::getComponent('com_tz_portfolio');
		$items		= $menus->getItems('component_id', $component->id);


        foreach ($items as $item)
        {

            if (isset($item->query) && isset($item->query['view'])) {
                $view = $item->query['view'];


                if (isset($item->query['id'])) {
                    if ($item->query['id'] == $cid) {
                        return $item -> id;
                    }
                } else {

                    $catids = $item->params->get('tz_catid');
                    if ($view == 'portfolio' && $catids) {
                        if (is_array($catids)) {
                            for ($i = 0; $i < count($catids); $i++) {
                                if ($catids[$i] == 0 || $catids[$i] == $cid) {
                                    return $item -> id;
                                }
                            }
                        } else {
                            if ($catids == $cid) {
                                return $item -> id;
                            }
                        }
                    }
                    elseif($view == 'category' && $catids){
                        return $item -> id;
                    }
                }
            }
        }

		return $active -> id;
	}
}