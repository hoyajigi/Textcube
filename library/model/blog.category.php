<?php
/// Copyright (c) 2004-2014, Needlworks  / Tatter Network Foundation
/// All rights reserved. Licensed under the GPL.
/// See the GNU General Public License for more details. (/documents/LICENSE, /documents/COPYRIGHT)


function getCategoryId($blogid, $name, $parentName = false) {	
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
	if($result = MMCache::queryRow($context->getProperty('category.raw'),'name',$name)) {
		if($parentName == false) {
			return $result['id'];
		} else {
			$parent = MMCache::queryRow($context->getProperty('category.raw'),'name',$parentName);
			if($parent['id'] == $result['parent']) return $result['id'];
		}
	}
	return null;
}

function getCategoryIdByLabel($blogid, $label) {
	if (empty($label))
		return 0;
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
	if($result = MMCache::queryRow($context->getProperty('category.raw'),'label',$label))
		return $result['id'];
	else return null;
}

function getCategoryNameById($blogid, $id) {
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
	if($result = MMCache::queryRow($context->getProperty('category.raw'),'id',$id))
		return $result['name'];
	else return _text('전체');
}

function getCategoryBodyIdById($blogid, $id) {
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
	$result = MMCache::queryRow($context->getProperty('category.raw'),'id',$id);
	if (($id === 0) || ($result == '') || ($id === null))
		return 'tt-body-category';
	else return $result['bodyid'];
}

function getEntriesCountByCategory($blogid, $id) {
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
	$result = MMCache::queryRow($context->getProperty('category.raw'),'id',$id);
	if (($id === 0) || ($result == '') || ($id === null)) {
		return 0;
	} else {
		if(doesHaveOwnership() && Acl::check('group.editors')) return $result['entriesinlogin'];
		else return $result['entries'];
	}
}

function getCategoryLabelById($blogid, $id) {
	if ($id === null)
		return '';
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
	if($result = MMCache::queryRow($context->getProperty('category.raw'),'id',$id))
		return $result['label'];
	else return _text('분류 전체보기');
}

function getCategoryLinkById($blogid, $id) {
	if (($id === null) || ($id === 0))
		return '';
	$result = getCategoryNameById($blogid,$id);
	if($children = getParentCategoryId($blogid, $id)) {
		$result = rawurlencode(htmlspecialchars(escapeURL(getCategoryNameById($blogid,$children)))).'/'.rawurlencode(htmlspecialchars(escapeURL($result)));
	} else {
		$result = rawurlencode(htmlspecialchars(escapeURL($result)));
	}
	return $result;
}

function getCategory($blogid, $id = null, $field = null) {	
	if ($id === null)
		return '';
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.

	if($result = MMCache::queryRow($context->getProperty('category.raw'),'id',$id)) {
		if(!empty($field) && isset($result[$field])) {
			return $result[$field];
		} else return $result;
	} else return false;
}

function getCategories($blogid, $format = 'tree') {
	$context = Model_Context::getInstance();	
	if($format == 'tree' && $context->getProperty('category.tree') !== null)
		return $context->getProperty('category.tree');
	else if($format == 'raw' &&  $context->getProperty('category.raw') !== null)
		return $context->getProperty('category.raw');
	
	$pool = DBModel::getInstance();
	$pool->reset('Categories');
	$pool->setQualifier('blogid','equals',$blogid);
	$pool->setQualifier('id','beq',0);
	$pool->setOrder('parent , priority');
	$rows = $pool->getAll();
	
	$categories = array();
	if( empty($rows) ) {
		$rows = array();
	}
	if($format == 'raw') {
		foreach ($rows as $category) {
			$categories[$category['id']] = $category;
		}
		$context->setProperty('category.raw',$categories);
		return $categories;
	}
	foreach ($rows as $category) {
		if ($category['parent'] == null) {
			$category['children'] = array();
			$categories[$category['id']] = $category;
		} else if (isset($categories[$category['parent']])) {
			array_push($categories[$category['parent']]['children'], $category);
		}
	}
	$context->setProperty('category.tree',$categories);
	return $categories;
}

function getCategoryVisibilityList($blogid, $mode = 'private') {
	$context = Model_Context::getInstance();
	if($context->getProperty('category.visibilityList'.$mode) === null) {
		switch($mode) {
			case 'public':
				$visibility = 2;
				break;
			case 'private':
			default:
				$visibility = 1;
		}
				if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
		if($list = MMCache::queryColumn($context->getProperty('category.raw'),'visibility',$visibility,'id')) {
			$CategoryVisibilityList[$mode] = $list;
		} else {
			$CategoryVisibilityList[$mode] = array();
		}
		$context->setProperty('category.visibilityList'.$mode, $CategoryVisibilityList[$mode]);
	}
	return $context->getProperty('category.visibilityList'.$mode);
}

function getPrivateCategoryExclusionQuery($blogid) {
	$exclusionList = getCategoryVisibilityList($blogid, 'private');
	if(empty($exclusionList)) return '';
	return '  AND e.category NOT IN ('.implode(',',$exclusionList).')';
}

function getPrivateCategoryExclusionId($blogid) {
	$exclusionList = getCategoryVisibilityList($blogid, 'private');
	if(empty($exclusionList)) return null;
	return '  AND e.category NOT IN ('.implode(',',$exclusionList).')';
}

function getCategoriesSkin() {
	$ctx = Model_Context::getInstance();
	$setting = Setting::getSkinSettings(getBlogId());
	$skin = array('name' => "{$setting['skin']}",
			'url'               => $ctx->getProperty('service.path') . "/skin/tree/{$setting['tree']}",
			'labelLength'       => $setting['labelLengthOnTree'],
			'showValue'         => $setting['showValueOnTree'],
			'itemColor'         => "{$setting['colorOnTree']}",
			'itemBgColor'       => "{$setting['bgColorOnTree']}",
			'activeItemColor'   => "{$setting['activeColorOnTree']}",
			'activeItemBgColor' => "{$setting['activeBgColorOnTree']}", );
	return $skin;
}

function getParentCategoryId($blogid, $id) {
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.	
	
	if($result = MMCache::queryRow($context->getProperty('category.raw'),'id',$id))
		return $result['parent'];
	return null;
}

function getChildCategoryId($blogid, $id) {
	$context = Model_Context::getInstance();
	if($context->getProperty('category.raw')=== null) getCategories($blogid, 'raw'); //To cache category information.
	if($result = MMCache::queryColumn($context->getProperty('category.raw'),'parent',$id,'id'))
		return $result;
	return null;
}

function getNumberChildCategory($id = null) {
	$pool = DBModel::getInstance();
	$pool->reset('Categories');
	$pool->setQualifier('blogid','eq',getBlogId());
	$pool->setQualifier('parent','eq',($id === null ? NULL : $id));
	return $pool->getCount();
}

function getNumberEntryInCategories($id) {
	$pool = DBModel::getInstance();
	$pool->reset('Entries');
	$pool->setQualifier('blogid','eq',getBlogId());
	$pool->setQualifier('draft','eq',0);
	$pool->setQualifier('category','eq',($id === null ? NULL : $id));
	return $pool->getCount();
}

function addCategory($blogid, $parent, $name, $id = null, $priority = null) {
	$pool = DBModel::getInstance();
	if (empty($name))
		return false;
	if (!is_null($parent) && !Validator::id($parent))
		return false;
	if(!is_null($id) && !Validator::isInteger($id,0)) {
		return false;
	}
	if($priority !== null && !Validator::isInteger($priority,0)) {
		return false;
	}

	if (!is_null($parent)) {
		$pool->reset('Categories');
		$pool->setQualifier('blogid','eq',$blogid);
		$pool->setQualifier('id','eq',$parent);
		$label = $pool->getCell('name');
		if ($label === null)
			return false;
		$label .= '/' . $name;
	} else {
		$parent = 'NULL';
		$label = $name;
	}

	$label = Utils_Unicode::lessenAsEncoding($label, 255);
	$name = Utils_Unicode::lessenAsEncoding($name, 127);
	$pool->reset('Categories');
	$pool->setQualifier('blogid','eq',$blogid);
	$pool->setQualifier('name','eq',$name,true);
	if($parent == 'NULL') {
		$pool->setQualifier('parent','eq',NULL);
	} else {
		$pool->setQualifier('parent','eq',$parent);
	}
	if($pool->getCount() > 0) {
		return false;
	}

	if(!is_null($priority)) {
		$pool->reset('Categories');
		$pool->setQualifier('blogid','eq',$blogid);
		$pool->setQualifier('priority','eq',$priority);
		if($pool->doesExist()) {
			return false;
		} else {
			$newPriority = $priority;
		}
	} else {
		$pool->reset('Categories');
		$pool->setQualifier('blogid','eq',$blogid);
		$newPriority = $pool->getCell('MAX(priority)') + 1;
	}

	// Determine ID.
	if(!is_null($id)) {
		$pool->reset('Categories');
		$pool->setQualifier('blogid','eq',$blogid);
		$pool->setQualifier('id','eq',$id);
		if($pool->doesExist()) {
			return false;
		} else {
			$newId = $id;
		}
	} else {
		$pool->reset('Categories');
		$pool->setQualifier('blogid','eq',$blogid);
		$newId = $pool->getCell('MAX(id)') + 1;
	}
	$pool->reset('Categories');
	$pool->setAttribute('blogid',$blogid);
	$pool->setAttribute('id',$newId);
	if($parent == 'NULL') {
		$pool->setAttribute('parent',NULL);
	} else {
		$pool->setAttribute('parent',$parent);
	}
	$pool->setAttribute('name',$name,true);
	$pool->setAttribute('priority',$newPriority);
	$pool->setAttribute('entries',0);
	$pool->setAttribute('entriesinlogin',0);
	$pool->setAttribute('label',$label,true);
	$pool->setAttribute('visibility',2);
	$result = $pool->insert();
	updateEntriesOfCategory($blogid,$newId);
	return $result ? true : false;
}

function deleteCategory($blogid, $id) {	
	if (!is_numeric($id))
		return false;
	CacheControl::flushCategory($id);
	$pool = DBModel::getInstance();	
	$pool->reset('Categories');
	$pool->setQualifier('blogid','eq',$blogid);
	$pool->setQualifier('id','eq',$id);
	$pool->delete();
	updateEntriesOfCategory($blogid);
	return true;
}

function modifyCategory($blogid, $id, $name, $bodyid) {
	$ctx = Model_Context::getInstance();
	requireModel('blog.feed');
	if($id==0) checkRootCategoryExistence($blogid);
	if ((empty($name)) && (empty($bodyid)))
		return false;
	$row = POD::queryRow("SELECT p.name, p.id
		FROM ".$ctx->getProperty('database.prefix')."Categories c
		LEFT JOIN ".$ctx->getProperty('database.prefix')."Categories p ON c.parent = p.id
		WHERE c.blogid = $blogid AND c.id = $id");
	$label = $row['name'];
//	$parentId = $row['id'];
//	if (!empty($parentId)) {
//		$parentStr = "AND parent = $parentId";
//	} else
//		$parentStr = 'AND parent is null';
	$name = POD::escapeString(Utils_Unicode::lessenAsEncoding($name, 127));
	$bodyid = POD::escapeString(Utils_Unicode::lessenAsEncoding($bodyid, 20));
	if(POD::queryExistence("SELECT name
		FROM ".$ctx->getProperty('database.prefix')."Categories
		WHERE blogid = $blogid AND name = '".$name."' AND bodyid = '".$bodyid."'"))
		return false;
	$label = POD::escapeString(Utils_Unicode::lessenAsEncoding(empty($label) ? $name : "$label/$name", 255));
	$sql = "SELECT *
		FROM ".$ctx->getProperty('database.prefix')."Categories
		WHERE blogid = $blogid
			AND id = $id";
	// $sql = "SELECT count(*) FROM ".$ctx->getProperty('database.prefix')."Categories WHERE blogid = $blogid AND name='$name' $parentStr";
	if(POD::queryExistence($sql) == false)
		return false;

	$result = POD::query("UPDATE ".$ctx->getProperty('database.prefix')."Categories
		SET name = '$name',
			label = '$label',
			bodyid = '$bodyid'
		WHERE blogid = $blogid
			AND id = $id");
	if ($result)
		clearFeed();
	updateEntriesOfCategory($blogid,$id);
	CacheControl::flushCategory($id);
	return $result ? true : false;
}

function updateCategoryByEntryId($blogid, $entryId, $action = 'add',$parameters = null) {
	clearCategoryCache();
	$entry = getEntry($blogid, $entryId);
	// for deleteEntry
	if(is_null($entry) and isset($parameters['entry']))
		$entry = $parameters['entry'];
	$categoryId = $entry['category'];

	$parent       = getParentCategoryId($blogid, $categoryId);
	$categories = array($categoryId=>$action);

	switch($action) {
		case 'add':
			updateCategoryByCategoryId($blogid, $categoryId, 'add', array('visibility'=> $entry['visibility']));
			if(!empty($parent)) updateCategoryByCategoryId($blogid, $parent, 'add', array('visibility'=> $entry['visibility']));
			break;
		case 'delete':
			updateCategoryByCategoryId($blogid, $categoryId, 'delete', array('visibility'=> $entry['visibility']));
			if(!empty($parent)) updateCategoryByCategoryId($blogid, $parent, 'delete', array('visibility'=> $entry['visibility']));
			break;
		case 'update':
			
			if(isset($parameters['category']) && $parameters['category'][0] != $parameters['category'][1]) { // category is changed. oldcategory - 1, newcategory + 1.
				$oldparent = getParentCategoryId($blogid, $parameters['category'][0]);
				$newparent = getParentCategoryId($blogid, $parameters['category'][1]);
						
				if(is_null($oldparent) && !is_null($newparent) && $parameters['category'][0] == $newparent) { // level 1 -> 2. newcategory + 1
					updateCategoryByCategoryId($blogid, $parameters['category'][1], 'add', array('visibility'=>$parameters['visibility'][1]));
				} else if(!is_null($oldparent) && is_null($newparent) && $parameters['category'][1] == $oldparent) { // level 2 -> 1. oldcategory - 1
					updateCategoryByCategoryId($blogid, $parameters['category'][0], 'delete', array('visibility'=>$parameters['visibility'][0]));
				} else { // etcs
					updateCategoryByCategoryId($blogid, $parameters['category'][0], 'delete', array('visibility'=>$parameters['visibility'][0]));						
					updateCategoryByCategoryId($blogid, $parameters['category'][1], 'add', array('visibility'=>$parameters['visibility'][1]));						

					if(!is_null($oldparent))
						updateCategoryByCategoryId($blogid, $oldparent, 'delete', array('visibility'=>$parameters['visibility'][0]));	
					if(!is_null($newparent))
						updateCategoryByCategoryId($blogid, $newparent, 'add', array('visibility'=>$parameters['visibility'][1]));
				}	

			} else {	// Same category case. should see the visibility change
				if(isset($parameters['visibility']) && $parameters['visibility'][0] != $parameters['visibility'][1]) {
					updateCategoryByCategoryId($blogid, $categoryId, 'update', $parameters);	
				}
			}
		default:
			break;
	}
}

function updateCategoryByCategoryId($blogid, $categoryid, $action = 'add', $parameters = null) {

	$count        = getCategory($blogid, $categoryid, 'entries');
	$countInLogin = getCategory($blogid, $categoryid, 'entriesinlogin');
	if(empty($count)) $count = 0;
	if(empty($countInLogin)) $countInLogin = 0;
	switch($action) {
		case 'add':
			$countInLogin += 1;
			if(isset($parameters['visibility'])) {
				if($parameters['visibility'] > 1) $count += 1;
			}
			break;
		case 'delete':
			$countInLogin -= 1;
			if(isset($parameters['visibility'])) {
				if($parameters['visibility'] > 1) $count -= 1;
			}
			break;
		case 'update':
			if(isset($parameters['visibility'])) {
				if($parameters['visibility'][0]	< 2 && $parameters['visibility'][1] > 1) { // private -> public
					$count += 1;
				} else if($parameters['visibility'][0] > 1 && $parameters['visibility'][1] < 2) { // public -> private
					$count -= 1;
				} else { // no change
					return true;
				}
			}
	}
	$pool = DBModel::getInstance();
	$pool->reset('Categories');
	$pool->setQualifier('blogid','eq',$blogid);
	$pool->setQualifier('id','eq',$categoryid,false);
	$pool->setAttribute('entries',$count,false);
	$pool->setAttribute('entriesinlogin',$countInLogin,false);
	return $pool->update();
}

function updateEntriesOfCategory($blogid, $categoryId = - 1) {
	$ctx = Model_Context::getInstance();
	clearCategoryCache();

	if ($categoryId == -1) {
		$result = POD::queryAll("SELECT * FROM ".$ctx->getProperty('database.prefix')."Categories WHERE blogid = $blogid AND parent IS NULL");
	} else {
		$parent = getParentCategoryId($blogid, $categoryId);
		if (empty($parent)) {	// It is parent.
			$lookup = $categoryId;
		} else {
			$lookup = $parent;
		}
		$result = POD::queryAll("SELECT * FROM ".$ctx->getProperty('database.prefix')."Categories WHERE blogid = $blogid AND id = $lookup");	
	}
	
	foreach($result as $row) {
		$parent = $row['id'];
		$parentName = Utils_Unicode::lessenAsEncoding($row['name'], 127);
		$row['name'] = POD::escapeString($parentName);
		$countParent = POD::queryCell("SELECT COUNT(id) FROM ".$ctx->getProperty('database.prefix')."Entries WHERE blogid = $blogid AND draft = 0 AND visibility > 0 AND category = $parent");
		$countInLoginParent = POD::queryCell("SELECT COUNT(id) FROM ".$ctx->getProperty('database.prefix')."Entries WHERE blogid = $blogid AND draft = 0 AND category = $parent");
		$result2 = POD::queryAll("SELECT * FROM ".$ctx->getProperty('database.prefix')."Categories WHERE blogid = $blogid AND parent = $parent");
		foreach ($result2 as $rowChild) {
			$label = POD::escapeString(Utils_Unicode::lessenAsEncoding($parentName . '/' . $rowChild['name'], 255));
			$rowChild['name'] = POD::escapeString(Utils_Unicode::lessenAsEncoding($rowChild['name'], 127));
			$countChild = POD::queryCell("SELECT COUNT(id) FROM ".$ctx->getProperty('database.prefix')."Entries WHERE blogid = $blogid AND draft = 0 AND visibility > 0 AND category = {$rowChild['id']}");
			$countInLogInChild = POD::queryCell("SELECT COUNT(id) FROM ".$ctx->getProperty('database.prefix')."Entries WHERE blogid = $blogid AND draft = 0 AND category = {$rowChild['id']}");
			POD::query("UPDATE ".$ctx->getProperty('database.prefix')."Categories SET entries = $countChild, entriesinlogin = $countInLogInChild, label = '$label' WHERE blogid = $blogid AND id = {$rowChild['id']}");
			$countParent += $countChild;
			$countInLoginParent += $countInLogInChild;
		}
		POD::query("UPDATE ".$ctx->getProperty('database.prefix')."Categories SET entries = $countParent, entriesinlogin = $countInLoginParent, label = '{$row['name']}' WHERE blogid = $blogid AND id = $parent");
	}
	if($categoryId >=0) CacheControl::flushCategory($categoryId);
	return true;
}

function moveCategory($blogid, $id, $direction) {
	$ctx = Model_Context::getInstance();
	
	if ($direction == 'up') {
		$sign = '<';
		$arrange = 'DESC';
	} else {
		$sign = '>';
		$arrange = 'ASC';
	}
	$myId = $id;
	$myPriority = '';
	$myParent = '';
	$parentId = '';
	$parentPriority = '';
//	$parentParent = '';
	$myIsHaveChild = '';
	$nextId = '';
//	$nextParentId = '';
	$nextPriority = '';
	$sql = "SELECT
				_parent.id AS parentId,
				_parent.priority AS parentPriority,
				_parent.parent AS parentParent,
				_my.priority AS myPriority,
				_my.parent AS myParent
			FROM ".$ctx->getProperty('database.prefix')."Categories AS _my
				LEFT JOIN ".$ctx->getProperty('database.prefix')."Categories AS _parent ON _parent.id = _my.parent
			WHERE _my.id = $id AND _my.blogid = $blogid";
	$row = POD::queryRow($sql);
	$myParent = is_null($row['myParent']) ? 'NULL' : $row['myParent'];
	$parentId = is_null($row['parentId']) ? 'NULL' : $row['parentId'];
	$parentPriority = is_null($row['parentPriority']) ? 'NULL' : $row['parentPriority'];
//	$parentParent = is_null($row['parentParent']) ? 'NULL' : $row['parentParent'];
	$myPriority = $row['myPriority'];
	$sql = "SELECT count(*) FROM ".$ctx->getProperty('database.prefix')."Categories WHERE parent = $myId AND blogid = $blogid";
	$myIsHaveChild = (POD::queryCell($sql) > 0) ? true : false;
	$aux = $parentId == 'NULL' ? 'parent is null' : "parent = $parentId";
	$sql = "SELECT id, parent, priority FROM ".$ctx->getProperty('database.prefix')."Categories WHERE $aux AND blogid = $blogid AND id != 0 AND priority $sign $myPriority ORDER BY priority $arrange LIMIT 1";
//	$canMove = (POD::queryCount($sql) > 0) ? true : false;
	$row = POD::queryRow($sql);
	$nextId = is_null($row['id']) ? 'NULL' : $row['id'];
//	$nextParentId = is_null($row['parent']) ? 'NULL' : $row['parent'];
	$nextPriority = is_null($row['priority']) ? 'NULL' : $row['priority'];
	// 이동할 자신이 1 depth 카테고리일 때.
	if ($myParent == 'NULL') {
		// 자신이 2 depth를 가지고 있고, 위치를 바꿀 대상 카테고리가 있는 경우.
		if ($myIsHaveChild && $nextId != 'NULL') {
			$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
						SET
							priority = $myPriority
						WHERE
							id = $nextId AND blogid = $blogid";
			POD::query($sql);
			$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
						SET
							priority = $nextPriority
						WHERE
							id = $myId AND blogid = $blogid";
			POD::query($sql);
		// 자신이 2 depth를 가지지 않은 1 depth 카테고리이거나, 위치를 바꿀 대상이 없는 경우.
		} else {
			// 위치를 바꿀 대상 카테고리에 같은 이름이 존재하는지 판별.
			$myName = POD::queryCell("SELECT name FROM ".$ctx->getProperty('database.prefix')."Categories WHERE id = $myId AND blogid = $blogid");
			$overlapCount = POD::queryCell("SELECT count(*) FROM ".$ctx->getProperty('database.prefix')."Categories WHERE name = '$myName' AND parent = $nextId AND blogid = $blogid");
			// 같은 이름이 없으면 이동 시작.
			if ($overlapCount == 0) {
				$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
							SET
								parent = $nextId
							WHERE
								id = $myId AND blogid = $blogid";
				POD::query($sql);
				$sql = "SELECT id, priority FROM ".$ctx->getProperty('database.prefix')."Categories WHERE parent = $nextId AND blogid = $blogid ORDER BY priority DESC";
				$row = POD::queryRow($sql);
				$nextId = is_null($row['id']) ? 'NULL' : $row['id'];
				$nextPriority = is_null($row['priority']) ? 'NULL' : $row['priority'];
				if ($nextId != 'NULL') {
					$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
								SET
									priority = " . max($nextPriority, $myPriority) . "
								WHERE
									id = $nextId AND blogid = $blogid";
					POD::query($sql);
					$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
								SET
									priority = " . min($nextPriority, $myPriority) . "
								WHERE
									id = $myId AND blogid = $blogid";
					POD::query($sql);
				}
			// 같은 이름이 있으면.
			} else {
				$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
							SET
								priority = $myPriority
							WHERE
								id = $nextId AND blogid = $blogid";
				POD::query($sql);
				$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
							SET
								priority = $nextPriority
							WHERE
								id = $myId AND blogid = $blogid";
				POD::query($sql);
			}
		}
	// 이동할 자신이 2 depth일 때.
	} else {
		// 위치를 바꿀 대상이 1 depth이면.
		if ($nextId == 'NULL') {
			$myName = POD::escapeString(POD::queryCell("SELECT name FROM ".$ctx->getProperty('database.prefix')."Categories WHERE id = $myId and blogid = $blogid"));
			$overlapCount = POD::queryCell("SELECT count(*) FROM ".$ctx->getProperty('database.prefix')."Categories WHERE name = '$myName' AND parent IS NULL AND blogid = $blogid");
			// 1 depth에 같은 이름이 있으면 2 depth로 직접 이동.
			if ($overlapCount > 0) {
				$sql = "SELECT id, parent, priority FROM ".$ctx->getProperty('database.prefix')."Categories WHERE parent IS NULL AND blogid = $blogid AND priority $sign $parentPriority ORDER BY priority $arrange";
				$result = POD::queryAll($sql);
				foreach($result as $row) {
					$nextId = $row['id'];
//					$nextParentId = $row['parent'];
					$nextPriority = $row['priority'];

					// 위치를 바꿀 대상 카테고리에 같은 이름이 존재하는지 판별.
					$myName = POD::escapeString(POD::queryCell("SELECT name FROM ".$ctx->getProperty('database.prefix')."Categories WHERE id = $myId AND blogid = $blogid"));
					$overlapCount = POD::queryCell("SELECT count(*) FROM ".$ctx->getProperty('database.prefix')."Categories WHERE name = '$myName' AND parent = $nextId AND blogid = $blogid");
					// 같은 이름이 없으면 이동 시작.
					if ($overlapCount == 0) {
						$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
									SET
										parent = $nextId
									WHERE
										id = $myId AND blogid = $blogid";
						POD::query($sql);
							break;
					}
				}
			// 같은 이름이 없으면 1 depth로 이동.
			} else {
				$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories SET parent = NULL WHERE id = $myId AND blogid = $blogid";
				POD::query($sql);
				$sql = "SELECT id, priority FROM ".$ctx->getProperty('database.prefix')."Categories WHERE parent is null AND blogid = $blogid AND priority $sign $parentPriority ORDER BY priority $arrange";
				$row = POD::queryRow($sql);
				$nextId = is_null($row['id']) ? 'NULL' : $row['id'];
				$nextPriority = is_null($row['priority']) ? 'NULL' : $row['priority'];
				if ($nextId == 'NULL') {
					$operator = ($direction == 'up') ? '-' : '+';
					$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories SET priority = $parentPriority $operator 1 WHERE id = $myId AND blogid = $blogid";
					POD::query($sql);
				} else {
					if ($direction == 'up') {
						$aux = "SET priority = priority+1 WHERE priority >= $parentPriority AND blogid = $blogid";
						$aux2 = "SET priority = $parentPriority WHERE id = $myId AND blogid = $blogid";
					} else {
						$aux = "SET priority = priority+1 WHERE priority >= $nextPriority AND blogid = $blogid";
						$aux2 = "SET priority = $nextPriority WHERE id = $myId AND blogid = $blogid";
					}
					$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories $aux";
					POD::query($sql);
					$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories $aux2";
					POD::query($sql);
				}
			}
		// 위치를 바꿀 대상이 2 depth이면 위치 교환.
		} else {
			$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
						SET
							priority = $myPriority
						WHERE
							id = $nextId AND blogid = $blogid";
			POD::query($sql);
			$sql = "UPDATE ".$ctx->getProperty('database.prefix')."Categories
						SET
							priority = $nextPriority
						WHERE
							id = $myId AND blogid = $blogid";
			POD::query($sql);
		}
	}
	updateEntriesOfCategory($blogid);
	CacheControl::flushCategory($id);
}

function checkRootCategoryExistence($blogid) {
	$pool = DBModel::getInstance();	
	$pool->reset('Categories');
	$pool->setQualifier('blogid','eq',$blogid);
	$pool->setQualifier('id','eq',0);
	$childCategories = $pool->getCount();
		
	if(!($pool->getCount())) {
		$name = _text('전체');
		$result = addCategory($blogid,null,$name,0);
		return $result ? true : false;
	}
	return false;
}

function getCategoryVisibility($blogid, $id) {
	$categories = getCategories($blogid,'raw');
	if( isset($categories[$id]) ) {
		if( isset( $categories[$id]['visibility'] ) ) {
			return $categories[$id]['visibility'];
		}
	}
	return 2;
}

function getParentCategoryVisibility($blogid, $id) {
	if($id == 0) return false;
	$categories = getCategories($blogid,'raw');
	$parentId = $categories[$id]['parent'];
	if(!isset($parentId) || $parentId == NULL) return false;
	$parentVisibility = $categories[$parentId]['visibility'];
	if (empty($parentVisibility))
		return 2;
	else
		return $parentVisibility;
}

function setCategoryVisibility($blogid, $id, $visibility) {
	requireModel('blog.feed');
	if($id == 0) return false;
	$parentVisibility = getParentCategoryVisibility($blogid, $id);
	if ($parentVisibility!==false && $parentVisibility < 2) return false; // return without changing if parent category is set to hidden.
	
	$pool = DBModel::getInstance();	
	$pool->reset('Categories');
	$pool->setAttribute('visibility',$visibility);
	$pool->setQualifier('blogid','eq',$blogid);
	$pool->setQualifier('id','eq',$id);
	$result = $pool->update();
	if ($result && $visibility == 1) $result = setChildCategoryVisibility($blogid, $id, $visibility);
	if ($result)
		clearFeed();
	updateEntriesOfCategory($blogid);
	CacheControl::flushCategory($id);
	return $result ? $visibility : false;
}

function setChildCategoryVisibility($blogid, $id, $visibility) {
	if($id == 0) return false;
	$pool = DBModel::getInstance();	
	$pool->reset('Categories');
	$pool->setQualifier('blogid','eq',$blogid);
	$pool->setQualifier('parent','eq',$id);
	$childCategories = $pool->getColumn('id');
	
	if($childCategories!=false) {
		foreach($childCategories as $childCategory) {
			$pool->reset('Categories');
			$pool->setAttribute('visibility',$visibility);
			$pool->setQualifier('blogid','eq',$blogid);
			$pool->setQualifier('id','eq',$childCategory);			
			$result = $pool->update();
			if($result == false) return false;
		}
		return $result ? $visibility : false;
	}
	return $visibility;
}

function clearCategoryCache() {
	$context = Model_Context::getInstance();
	$context->setProperty('category.tree',null);
	$context->setProperty('category.raw',null);
}
?>
