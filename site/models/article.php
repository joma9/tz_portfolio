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

// No direct access
defined('_JEXEC') or die;

jimport('joomla.application.component.modelitem');

/**
 * Content Component Article Model.
 */
class TZ_PortfolioModelArticle extends JModelItem
{
	/**
	 * Model context string.
	 *
	 * @var		string
	 */
	protected $_context = 'com_tz_portfolio.article';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @since	1.6
	 */
	protected function populateState()
	{
		$app = JFactory::getApplication('site');

		// Load state from the request.
		$pk = JRequest::getInt('id');
		$this->setState('article.id', $pk);

        $this -> setState('article.catid',null);

		$offset = JRequest::getUInt('limitstart');
		$this->setState('list.offset', $offset);

		// Load the parameters.
		$params = $app->getParams();
//        $params = JComponentHelper::getParams('com_content');
//        var_dump($params);
		$this->setState('params', $params);

		// TODO: Tune these values based on other permissions.
		$user		= JFactory::getUser();
		if ((!$user->authorise('core.edit.state', 'com_tz_portfolio')) &&  (!$user->authorise('core.edit', 'com_tz_portfolio'))){
			$this->setState('filter.published', 1);
			$this->setState('filter.archived', 2);
		}
	}

    public function download(){
        $query  = 'SELECT * FROM #__tz_portfolio_xref_content'
                  .' WHERE contentid='.JRequest::getInt('id');

        $db     = &JFactory::getDbo();
        $db -> setQuery($query);
        if(!$db -> query()){
            $this -> setError($db -> getErrorMsg());
            return false;
        }
        if(!$rows = $db -> loadObject()){
            $this -> setError($db -> getErrorMsg());
            return false;
        }

        $file   = '';
        $arr    = explode('///',$rows -> attachfiles);
        if(count($arr)>0){
            foreach($arr as $item){
                if(md5($item) == JRequest::getCmd('attach')){
                    $file   = $item;
                }
            }
        }

        return $file;
    }

    function getItemRelated($pk=null){
        // Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('article.id');

        if($pk){
            $params     = $this -> getState('params');
            $article    = $this -> getItem($pk);
            $params -> merge($article -> params);
            $limit      = $params->get('num_leading_articles') + $params->get('num_intro_articles') + $params->get('num_links');

            switch($params -> get('related_orderby','rdate')){
                default:
                    $orderBy    = 'c.created DESC';
                    break;
                case 'date':
                    $orderBy    = 'c.created ASC';
                    break;
                case 'hits':
                    $orderBy    = 'c.hits DESC';
                    break;
                case 'rhits':
                    $orderBy    = 'c.hits ASC';
                    break;
            }
            $orderBy    = ' ORDER BY '.$orderBy;

            $query  = 'SELECT c.*,CASE WHEN CHAR_LENGTH(c.alias) THEN CONCAT_WS(":", c.id, c.alias) ELSE c.id END as slug,'
                      .' CASE WHEN CHAR_LENGTH(cc.alias) THEN CONCAT_WS(":", cc.id, cc.alias) ELSE cc.id END as catslug'
                      .' FROM #__content AS c'
                      .' LEFT JOIN #__categories AS cc ON cc.id=c.catid'
                      .' WHERE NOT c.id='.$pk
                      .' AND c.catid='.$article -> catid
                      .$orderBy;
            $db     = &JFactory::getDbo();
            $db -> setQuery($query,0,$limit);
            if(!$db -> query()){
                $this -> setError($db -> getErrorMsg());
                return false;
            }

            return $db -> loadObjectList();
        }
    }

	/**
	 * Method to get article data.
	 *
	 * @param	integer	The id of the article.
	 *
	 * @return	mixed	Menu item data object on success, false on failure.
	 */
	public function &getItem($pk = null)
	{
		// Initialise variables.
		$pk = (!empty($pk)) ? $pk : (int) $this->getState('article.id');

		if ($this->_item === null) {
			$this->_item = array();
		}

		if (!isset($this->_item[$pk])) {

			try {
				$db = $this->getDbo();
				$query = $db->getQuery(true);

				$query->select($this->getState(
					'item.select', 'a.id, a.asset_id, a.title, a.alias, a.introtext, a.fulltext, ' .
					// If badcats is not null, this means that the article is inside an unpublished category
					// In this case, the state is set to 0 to indicate Unpublished (even if the article state is Published)
					'CASE WHEN badcats.id is null THEN a.state ELSE 0 END AS state, ' .
					'a.catid, a.created, a.created_by, a.created_by_alias, ' .
				// use created if modified is 0
				'CASE WHEN a.modified = 0 THEN a.created ELSE a.modified END as modified, ' .
					'a.modified_by, a.checked_out, a.checked_out_time, a.publish_up, a.publish_down, ' .
					'a.images, a.urls, a.attribs, a.version, a.ordering, ' .
					'a.metakey, a.metadesc, a.access, a.hits, a.metadata, a.featured, a.language, a.xreference'
					)
				);
				$query->from('#__content AS a');

				// Join on category table.
				$query->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access');
				$query->join('LEFT', '#__categories AS c on c.id = a.catid');

				// Join on user table.
				$query->select('u.name AS author');
				$query->join('LEFT', '#__users AS u on u.id = a.created_by');

				// Join on contact table
				$subQuery = $db->getQuery(true);
				$subQuery->select('contact.user_id, MAX(contact.id) AS id, contact.language');
				$subQuery->from('#__contact_details AS contact');
				$subQuery->where('contact.published = 1');
				$subQuery->group('contact.user_id, contact.language');
				$query->select('contact.id as contactid' );
				$query->join('LEFT', '(' . $subQuery . ') AS contact ON contact.user_id = a.created_by');

				// Join over the categories to get parent category titles
				$query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias');
				$query->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

				// Join on voting table
				$query->select('ROUND(v.rating_sum / v.rating_count, 0) AS rating, v.rating_count as rating_count');
				$query->join('LEFT', '#__content_rating AS v ON a.id = v.content_id');

				$query->where('a.id = ' . (int) $pk);

				// Filter by start and end dates.
				$nullDate = $db->Quote($db->getNullDate());
				$date = JFactory::getDate();

				$nowDate = $db->Quote($date->toSql());

				$query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
				$query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');

				// Join to check for category published state in parent categories up the tree
				// If all categories are published, badcats.id will be null, and we just use the article state
				$subquery = ' (SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
				$subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
				$subquery .= 'WHERE parent.extension = ' . $db->quote('com_content');
				$subquery .= ' AND parent.published <= 0 GROUP BY cat.id)';
				$query->join('LEFT OUTER', $subquery . ' AS badcats ON badcats.id = c.id');

				// Filter by published state.
				$published = $this->getState('filter.published');
				$archived = $this->getState('filter.archived');

				if (is_numeric($published)) {
					$query->where('(a.state = ' . (int) $published . ' OR a.state =' . (int) $archived . ')');
				}

				$db->setQuery($query);

				$data = $db->loadObject();

				if ($error = $db->getErrorMsg()) {
					throw new Exception($error);
				}

				if (empty($data)) {
					return JError::raiseError(404, JText::_('COM_CONTENT_ERROR_ARTICLE_NOT_FOUND'));
				}

				// Check for published state if filter set.
				if (((is_numeric($published)) || (is_numeric($archived))) && (($data->state != $published) && ($data->state != $archived))) {
					return JError::raiseError(404, JText::_('COM_CONTENT_ERROR_ARTICLE_NOT_FOUND'));
				}


				// Convert parameter fields to objects.
				$registry = new JRegistry;
				$registry->loadString($data->attribs);

				$data->params = clone $this->getState('params');
				$data->params->merge($registry);

				$registry = new JRegistry;
				$registry->loadString($data->metadata);
				$data->metadata = $registry;

				// Compute selected asset permissions.
				$user	= JFactory::getUser();

				// Technically guest could edit an article, but lets not check that to improve performance a little.
				if (!$user->get('guest')) {
					$userId	= $user->get('id');
					$asset	= 'com_tz_portfolio.article.'.$data->id;

					// Check general edit permission first.
					if ($user->authorise('core.edit', $asset)) {
						$data->params->set('access-edit', true);
					}
					// Now check if edit.own is available.
					elseif (!empty($userId) && $user->authorise('core.edit.own', $asset)) {
						// Check for a valid user and that they are the owner.
						if ($userId == $data->created_by) {
							$data->params->set('access-edit', true);
						}
					}
				}

				// Compute view access permissions.
				if ($access = $this->getState('filter.access')) {
					// If the access filter has been set, we already know this user can view.
					$data->params->set('access-view', true);
				}
				else {
					// If no access filter is set, the layout takes some responsibility for display of limited information.
					$user = JFactory::getUser();
					$groups = $user->getAuthorisedViewLevels();

					if ($data->catid == 0 || $data->category_access === null) {
						$data->params->set('access-view', in_array($data->access, $groups));
					}
					else {
						$data->params->set('access-view', in_array($data->access, $groups) && in_array($data->category_access, $groups));
					}
				}

				$this->_item[$pk] = $data;
			}
			catch (JException $e)
			{
				if ($e->getCode() == 404) {
					// Need to go thru the error handler to allow Redirect to work.
					JError::raiseError(404, $e->getMessage());
				}
				else {
					$this->setError($e);
					$this->_item[$pk] = false;
				}
			}
		}

		return $this->_item[$pk];
	}

	/**
	 * Increment the hit counter for the article.
	 *
	 * @param	int		Optional primary key of the article to increment.
	 *
	 * @return	boolean	True if successful; false otherwise and internal error set.
	 */
	public function hit($pk = 0)
	{
            $hitcount = JRequest::getInt('hitcount', 1);

            if ($hitcount)
            {
                // Initialise variables.
                $pk = (!empty($pk)) ? $pk : (int) $this->getState('article.id');
                $db = $this->getDbo();

                $db->setQuery(
                        'UPDATE #__content' .
                        ' SET hits = hits + 1' .
                        ' WHERE id = '.(int) $pk
                );

                if (!$db->query()) {
                        $this->setError($db->getErrorMsg());
                        return false;
                }
            }

            return true;
	}

    public function storeVote($pk = 0, $rate = 0)
    {
        if ( $rate >= 1 && $rate <= 5 && $pk > 0 )
        {
            $userIP = $_SERVER['REMOTE_ADDR'];
            $db = $this->getDbo();

            $db->setQuery(
                    'SELECT *' .
                    ' FROM #__content_rating' .
                    ' WHERE content_id = '.(int) $pk
            );

            $rating = $db->loadObject();

            if (!$rating)
            {
                // There are no ratings yet, so lets insert our rating
                $db->setQuery(
                        'INSERT INTO #__content_rating ( content_id, lastip, rating_sum, rating_count )' .
                        ' VALUES ( '.(int) $pk.', '.$db->Quote($userIP).', '.(int) $rate.', 1 )'
                );

                if (!$db->query()) {
                        $this->setError($db->getErrorMsg());
                        return false;
                }
            } else {
                if ($userIP != ($rating->lastip))
                {
                    $db->setQuery(
                            'UPDATE #__content_rating' .
                            ' SET rating_count = rating_count + 1, rating_sum = rating_sum + '.(int) $rate.', lastip = '.$db->Quote($userIP) .
                            ' WHERE content_id = '.(int) $pk
                    );
                    if (!$db->query()) {
                            $this->setError($db->getErrorMsg());
                            return false;
                    }
                } else {
                    return false;
                }
            }
            return true;
        }
        JError::raiseWarning( 'SOME_ERROR_CODE', JText::sprintf('COM_CONTENT_INVALID_RATING', $rate), "JModelArticle::storeVote($rate)");
        return false;
    }

    function getFindItemId($_cid=null)
	{
        $cid    = $this -> getState('article.catid');
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
