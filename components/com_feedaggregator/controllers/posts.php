<?php
/**
 * HUBzero CMS
 *
 * Copyright 2005-2011 Purdue University. All rights reserved.
 *
 * This file is part of: The HUBzero(R) Platform for Scientific Collaboration
 *
 * The HUBzero(R) Platform for Scientific Collaboration (HUBzero) is free
 * software: you can redistribute it and/or modify it under the terms of
 * the GNU Lesser General Public License as published by the Free Software
 * Foundation, either version 3 of the License, or (at your option) any
 * later version.
 *
 * HUBzero is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * HUBzero is a registered trademark of Purdue University.
 *
 * @package   hubzero-cms
 * @author   Kevin Wojkovich <kevinw@purdue.edu>
 * @copyright Copyright 2005-2014 Purdue University. All rights reserved.
 * @license   http://www.gnu.org/licenses/lgpl-3.0.html LGPLv3
 */

require_once(JPATH_ROOT.DS."components".DS."com_feedaggregator".DS."models".DS."feeds.php");
require_once(JPATH_ROOT.DS."components".DS."com_feedaggregator".DS."models".DS."posts.php");

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

use Guzzle\Http\Client;

/**
 *  Feed Aggregator controller class
 */
class FeedaggregatorControllerPosts extends \Hubzero\Component\SiteController
{
	public function displayTask($posts = NULL)
	{
		$this->_getScripts('assets/js/posts');
		$this->_getStyles();
		$document = JFactory::getDocument();
		$document->addStyleSheet('components' . DS . $this->_option . DS . 'assets' . DS . 'css' . DS . 'posts.css');
		
		$userId = $this->juser->id;
		$authlevel = JAccess::getAuthorisedViewLevels($userId);
		$access_level = 3; //author_level
		if(in_array($access_level,$authlevel) && JFactory::getUser()->id)
		{
			if(isset($posts))
			{
				$this->setView('posts','display');
				$this->view->fromfeed = TRUE;
			}
			else
			{
				$this->view->fromfeed = FALSE;
				$this->view->setLayout('display');
				// Incoming
				$this->view->filters = array();
				$this->view->filters['limit']    = JRequest::getInt('limit', 10);
				$this->view->filters['start']    = JRequest::getInt('limitstart', 0);
				$this->view->filters['time'] 	= JRequest::getString('timesort', '');
				$this->view->filters['filterby'] = JRequest::getString('filterby', 'all');
				
				// Don't have a 0, because then it won't return anything. Doing mysql-workbench default
				if($this->view->filters['limit'] == 0)
				{
					$this->view->filters['limit'] = 1000;
				}
				
				$feeds = array(); //page on websites
				$posts = array();
				
				$model = new FeedAggregatorModelPosts;
				
				if($this->view->filters['filterby'] == 'all')
				{
					$posts = $model->loadAllPosts($this->view->filters['limit'], $this->view->filters['start']);
				}
				else if($this->view->filters['filterby'] == 'new')
				{
					$posts = $model->getPostsByStatus($this->view->filters['limit'], $this->view->filters['start'],0);
				}
				else if($this->view->filters['filterby'] == 'approved')
				{
					$posts = $model->getPostsByStatus($this->view->filters['limit'], $this->view->filters['start'],2);
				}
				else if($this->view->filters['filterby'] == 'review')
				{
					$posts = $model->getPostsByStatus($this->view->filters['limit'], $this->view->filters['start'],1);
				}
				else if($this->view->filters['filterby'] == 'removed')
				{
					$posts = $model->getPostsByStatus($this->view->filters['limit'], $this->view->filters['start'],3);
				}
				else
				{
					//load stored posts
					$model = new FeedAggregatorModelPosts;
					$posts = $model->loadAllPosts($this->view->filters['limit'], $this->view->filters['start']);
				}
				
				$this->view->total = intval($model->loadRowCount());
				
				// Initiate paging
				jimport('joomla.html.pagination');
				$this->view->pageNav = new JPagination(
						$this->view->total,
						$this->view->filters['start'],
						$this->view->filters['limit']
				);
			}
			
			/*Truncates the title to save screen real-estate. Full version shown in FancyBox*/
			foreach ($posts as $post)
			{
				if(strlen($post->title) >= 60)
				{
					$string = substr($post->title, 0, 60);
					$string = substr($string, 0, strrpos($string, ' ')). "...";
					$post->shortTitle = $string;
				}
				else
				{
					$post->shortTitle = $post->title;
				}
			
				$epoch = $post->created;
				// convert UNIX timestamp to PHP DateTime
				$dt = new DateTime("@$epoch");  
				// output = 2012-08-15 00:00:00
				$post->created =  $dt->format('m-d-y h:i A'); 
			
				$post->description = wordwrap($post->description,100,"<br>\n");
				$post->title = wordwrap($post->title, 65, "<br>\n");
			
				switch($post->status)
				{
					case 0:
						$post->status = "new";
						break;
					case 1:
						$post->status = "under review";
						break;
					case 2:
						$post->status = "approved";
						break;
					case 3:
						$post->status = "removed";
				} //end switch
			} //end foreach
			$this->view->posts = $posts;
			$this->view->title =  JText::_('Feed Aggregator');
			$this->view->display();
		}
		else if(JFactory::getUser()->id)
		{
			$this->view->setLayout('feedurl');
			$this->view->title =  JText::_('Feed Aggregator');
			$this->view->display();
		}
		else if(JFactory::getUser()->id == FALSE) // have person login
		{
				$rtrn = JRequest::getVar('REQUEST_URI', JRoute::_('index.php?option=' . $this->_option . '&task=' . $this->_task), 'server') . '/?filterby=all';
				$this->setRedirect(
				JRoute::_('index.php?option=com_login&return=' . base64_encode($rtrn)),
				JText::_('COM_FEEDAGGREGATOR_LOGIN_NOTICE'),
				'warning'
				);
		}
	}
	
	public function updateStatusTask()
	{
		$id = JRequest::getVar('id', '');
		$action = JRequest::getVar('action', '');
		$model = new FeedAggregatorModelPosts;
	
		switch($action)
		{
			case "new":
				$action_id = 0;
				break;
			case "mark":
				$action_id = 1;
				break;
			case "approve":
				$action_id = 2;
				break;
			case "remove":
				$action_id = 3;
		} //end switch
		
		
		$model->updateStatus($id, $action_id);
		echo $action_id;	
		exit();
	}
	
	public function PostsByIdTask()
	{
		
		$id = JRequest::getVar('id', '');
		$model = new FeedAggregatorModelPosts;
		$posts = $model->loadPostsByFeedId($id);
		$this->displayTask($posts);
	
	}
	
	
		public function RetrieveNewPostsTask()
		{
			$model = new FeedAggregatorModelFeeds;
			$feeds = $model->loadAll();
			
			$model = new FeedAggregatorModelPosts;
			$savedURLS = $model->loadURLs();
			
			try {
				
				Guzzle\Http\StaticClient::mount();
						
				foreach ($feeds as $feed)
				{
					if($feed->enabled == 1)
					{
						$response = Guzzle::get($feed->url); //fetches URL
						$page =  $response->xml(); //returns response in XML format
					
						if(isset($page->entry) == TRUE)
						{
							$items = $page->entry;	
						}
						else
						{
							$items = $page->channel->item; //gets the items of the channel
						}
						
						foreach($items as $item)
						{	
							if(in_array($item->link, $savedURLS) == FALSE) //checks to see if we have this item
							{
								$post = new FeedAggregatorModelPosts;
								$post->set('title', (string)$item->title);
								$post->set('url', (string) $item->link);
								$post->set('feed_id', (integer) $feed->id);	
								$post->set('status', 0);
								if(isset($page->entry) == TRUE) //ATOM
								{
									if(isset($item->published) == TRUE)
									{
										$post->set('created', strtotime($item->published));
									}
									else
									{
										$post->set('created', strtotime($item->updated));
									}
									
									$post->set('description', (string) strip_tags($item->summary, '<img>'));
								}
								else // RSS 
								{
									$post->set('created', strtotime($item->pubDate));
									$post->set('description', (string) strip_tags($item->description, '<img>'));
								}
		
								$post->store();
							} // end if
		
						} //end foreach
					}//end if
				}
				// Output messsage and redirect
				$this->setRedirect(
						'index.php?option=' . $this->_option . '&controller=posts',
						JText::_('New Posts Retrieved.')
				);
			} //end try
			catch (Exception $e) {
				echo $e.'</br></br>';
				exit();
			}	
		}
	
	public function generateFeedTask()
	{
		// Get the approved posts
		$model = new FeedAggregatorModelPosts;
		$posts = $model->getPostsByStatus(1000,0,2);
		
		
		// Set the mime encoding for the document
		$jdoc = JFactory::getDocument();
		$jdoc->setMimeEncoding('application/rss+xml');
	
		// Start a new feed object
		$doc = new JDocumentFeed;
		$doc->link = JRoute::_('index.php?option=com_feedaggregator&task=generateFeed&no_html=1');
		
		// Build some basic RSS document information
		$jconfig = JFactory::getConfig();
		
		$doc->title       =  $jconfig->getValue('config.sitename'). " Aggregated Feed";
		$doc->description =  JText::_("Feed aggregator description");
		$doc->copyright   = JText::sprintf(date("Y"), $jconfig->getValue('config.sitename'));
		$doc->category    = JText::_('External content');
		
		// Start outputing results if any found
		if (count($posts) > 0)
		{
			foreach ($posts as $post)
			{
				$item = new JFeedItem();
				
				// Load individual item creator class
				$item->title       = html_entity_decode(strip_tags($post->title));
				$item->link        = $post->link;
				$item->date        = date($post->created);
				$item->description = $post->description;
	
				// Loads item info into rss array
				$doc->addItem($item);
			}
		}
	
		// Output the feed
		echo $doc->render();
		return;
	}
	
} // end class