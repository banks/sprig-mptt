<?php defined('SYSPATH') OR die('No direct access allowed.');
/**
 * Sprig_MPTT Unit Test
 *
 * @package    Sprig_MPTT
 * @author     Paul Banks
 */
class UnitTest_Sprig_MPTT extends UnitTest_Case {

	function __construct()
	{
		// Create test model database
		Sprig::factory('mptt_test')
			->create_table();
		
		/**
		 * Creates a table with the following trees
		 * 
		 * SCOPE 1
		 * 
		 *                   1[ 1 ]22
		 *                      |
		 *     -----------------------------------
		 *     |          |          |           |
		 *  2[ 2 ]3    4[ 3 ]7    8[ 5 ]9    10[ 6 ]21
		 *                |                      |
		 *             5[ 4 ]6      ---------------------------
		 *                          |            |            |
		 *                      11[ 7 ]12    13[ 8 ]18    19[ 11 ]20
		 *                                       |
		 *                                 --------------
		 *                                 |            |        
		 *                             14[ 9 ]15    16[ 10 ]17
		 *                             
		 * Then scopes 2 and 3 duplicate this structure with correspondingly higher IDs
		 * 
		 */
	}
	
	function teardown()
	{
		Sprig::factory('mptt_test')
			->reset_table();
	}
	
	function __destruct()
	{
		// Delete test model database
		Sprig::factory('mptt_test')
			->delete_table();
	}
	
	function test_insert_as_new_root()
	{
		$model = Sprig::factory('mptt_test');
		$model->name = "Test Root Node";
		
		$this
			// Scope 1 should already exist
			->assert_false($model->insert_as_new_root(1))
			// This should create scope 4 and return iteself
			->assert_equal($model->insert_as_new_root(4), $model)
			// Check new root has been give the correct MPTT values
			->assert_equal($model->lft, 1)
			->assert_equal($model->rgt, 2)
			->assert_equal($model->lvl, 0)
			->assert_equal($model->scope, 4)
			// Make sure we haven't invalidated the tree
			->assert_true($model->verify_tree());
	}
	
	function test_load()
	{
		$model = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_true($model->loaded())
			->assert_equal($model->lft, 4)
			->assert_equal($model->rgt, 7);
	}
	
	function test_root()
	{
		$root = Sprig::factory('mptt_test', array('id' => 1))->load();
		$node = Sprig::factory('mptt_test', array('id' => 8))->load();
		
		$this
			->assert_true($root->loaded())
			->assert_equal($node->root()->id, $root->id);
	}
	
	function test_has_children()
	{
		$node_with_one_child = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_with_children = Sprig::factory('mptt_test', array('id' => 8))->load();
		$leaf_node = Sprig::factory('mptt_test', array('id' => 5))->load();
		
		$this
			->assert_true($node_with_one_child->has_children())
			->assert_false($node_with_one_child->is_leaf())
			
			->assert_true($node_with_children->has_children())
			->assert_false($node_with_children->is_leaf())
			
			->assert_false($leaf_node->has_children())
			->assert_true($leaf_node->is_leaf());
	}
	
	function test_is_descendant()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_4 = Sprig::factory('mptt_test', array('id' => 4))->load();
		$root_node = $node_3->root();
		
		$this
			->assert_true($node_3->is_descendant($root_node))
			->assert_true($node_4->is_descendant($root_node))
			->assert_true($node_4->is_descendant($node_3))
			->assert_false($node_3->is_descendant($node_3))
			->assert_false($node_3->is_descendant($node_4));
	}
	
	function test_is_child()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_4 = Sprig::factory('mptt_test', array('id' => 4))->load();
		$node_5 = Sprig::factory('mptt_test', array('id' => 4))->load();
		$root_node = $node_3->root();
		
		$this
			->assert_true($node_4->is_child($node_3))
			->assert_true($node_3->is_child($root_node))
			->assert_false($node_4->is_child($root_node))
			->assert_false($node_4->is_child($node_5))
			->assert_false($node_4->is_child($node_4));
	}
	
	function test_is_parent()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_4 = Sprig::factory('mptt_test', array('id' => 4))->load();
		$node_5 = Sprig::factory('mptt_test', array('id' => 4))->load();
		$root_node = $node_3->root();
		
		$this
			->assert_true($node_3->is_parent($node_4))
			->assert_true($root_node->is_parent($node_3))
			->assert_false($root_node->is_parent($node_4))
			->assert_false($node_4->is_parent($node_5))
			->assert_false($node_4->is_parent($node_4));
	}
	
	function test_is_sibling()
	{
		$node_5 = Sprig::factory('mptt_test', array('id' => 5))->load();
		$node_6 = Sprig::factory('mptt_test', array('id' => 6))->load();
		$node_7 = Sprig::factory('mptt_test', array('id' => 7))->load();
		
		$this
			->assert_true($node_5->is_sibling($node_6))
			->assert_false($node_5->is_sibling($node_7))
			->assert_false($node_5->is_sibling($node_5));
	}
	
	function test_is_root()
	{
		$node_5 = Sprig::factory('mptt_test', array('id' => 5))->load();
		$root = $node_5->root();
		
		$this
			->assert_true($root->is_root())
			->assert_false($node_5->is_root());
	}
	
	function test_parent()
	{
		$node_5 = Sprig::factory('mptt_test', array('id' => 5))->load();
		$root = $node_5->root();
		
		$this
			->assert_similar($node_5->parent(), $root)
			->assert_false($root->parent()->loaded());
	}
	
	function test_parents()
	{
		$node_5 = Sprig::factory('mptt_test', array('id' => 5))->load();
		$node_9 = Sprig::factory('mptt_test', array('id' => 9))->load();
		
		$node_5_parents = $node_5->parents();
		$node_9_parents = $node_9->parents();
		$node_9_parents_desc = $node_9->parents(TRUE, 'DESC');
		
		$this
			->assert_instance($node_5_parents, 'Database_Result')
			->assert_count($node_5_parents, 1)
			->assert_count($node_5->parents(FALSE), 0)
			->assert_equal($node_5_parents[0]->id, 1)
			
			->assert_instance($node_9_parents, 'Database_Result')
			->assert_count($node_9_parents, 3)
			->assert_count($node_9->parents(FALSE), 2)
			->assert_equal($node_9_parents[0]->id, 1)
			->assert_equal($node_9_parents[1]->id, 6)
			->assert_equal($node_9_parents[2]->id, 8)
			
			->assert_equal($node_9_parents_desc[2]->id, 1)
			->assert_equal($node_9_parents_desc[1]->id, 6)
			->assert_equal($node_9_parents_desc[0]->id, 8);
	}
	
	function test_children()
	{
		$node_6 = Sprig::factory('mptt_test', array('id' => 6))->load();

		$node_6_children = $node_6->children();
		$node_6_children_desc = $node_6->children(FALSE, 'DESC');
		
		$this
			->assert_instance($node_6_children, 'Database_Result')
			->assert_count($node_6_children, 3)
			->assert_count($node_6->children(TRUE), 4)
			->assert_equal($node_6_children[0]->id, 7)
			->assert_equal($node_6_children[1]->id, 8)
			->assert_equal($node_6_children[2]->id, 11)
			
			->assert_equal($node_6_children_desc[0]->id, 11)
			->assert_equal($node_6_children_desc[1]->id, 8)
			->assert_equal($node_6_children_desc[2]->id, 7);
	}
	
	function test_descendants()
	{
		$node_6 = Sprig::factory('mptt_test', array('id' => 6))->load();

		$node_6_descendants = $node_6->descendants();
		
		$this
			->assert_instance($node_6_descendants, 'Database_Result')
			->assert_count($node_6_descendants, 5)
			->assert_count($node_6->descendants(TRUE), 6)
			->assert_equal($node_6_descendants[0]->id, 7)
			->assert_equal($node_6_descendants[1]->id, 8)
			->assert_equal($node_6_descendants[2]->id, 9)
			->assert_equal($node_6_descendants[3]->id, 10)
			->assert_equal($node_6_descendants[4]->id, 11);
	}
	
	function test_siblings()
	{	
		$node_5 = Sprig::factory('mptt_test', array('id' => 5))->load();

		$node_5_siblings = $node_5->siblings();
		
		$this
			->assert_instance($node_5_siblings, 'Database_Result')
			->assert_count($node_5_siblings, 3)
			->assert_count($node_5->siblings(TRUE), 4)
			->assert_equal($node_5_siblings[0]->id, 2)
			->assert_equal($node_5_siblings[1]->id, 3)
			->assert_equal($node_5_siblings[2]->id, 6);
	}
	
	function test_leaves()
	{
		$node_1 = Sprig::factory('mptt_test', array('id' => 1))->load();

		$node_1_leaves = $node_1->leaves();
		
		$this
			->assert_instance($node_1_leaves, 'Database_Result')
			->assert_count($node_1_leaves, 2)
			->assert_equal($node_1_leaves[0]->id, 2)
			->assert_equal($node_1_leaves[1]->id, 5);
	}

	function test_insert_as_first_child()
	{
		$new = Sprig::factory('mptt_test');
		$new->name = 'Test Element';
		
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->insert_as_first_child(4))
			->assert_equal($new->insert_as_first_child($node_3), $new);
			
		// Reload node 3 to check insert worked
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_3_children = $node_3->children();
		
		$this
			->assert_count($node_3_children, 2)
			->assert_equal($node_3_children[0]->id, $new->id)
			->assert_true($node_3->verify_tree());
	}

	function test_insert_as_last_child()
	{
		$new = Sprig::factory('mptt_test');
		$new->name = 'Test Element';
		
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->insert_as_last_child(4))
			->assert_equal($new->insert_as_last_child($node_3), $new);
			
		// Reload node 3 to check insert worked
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_3_children = $node_3->children();
		
		$this
			->assert_count($node_3_children, 2)
			->assert_equal($node_3_children[1]->id, $new->id)
			->assert_true($node_3->verify_tree());
	}

	function test_insert_as_prev_sibling()
	{
		$new = Sprig::factory('mptt_test');
		$new->name = 'Test Element';
		
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->insert_as_prev_sibling(4))
			->assert_equal($new->insert_as_prev_sibling($node_3), $new);
			
		// Reload node 3 to check insert worked
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_3_siblings = $node_3->siblings(TRUE);
		
		$this
			->assert_count($node_3_siblings, 5)
			->assert_equal($node_3_siblings[1]->id, $new->id)
			->assert_true($node_3->verify_tree());
	}

	function test_insert_as_next_sibling()
	{
		$new = Sprig::factory('mptt_test');
		$new->name = 'Test Element';
		
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->insert_as_next_sibling(4))
			->assert_equal($new->insert_as_next_sibling($node_3), $new);
			
		// Reload node 3 to check insert worked
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_3_siblings = $node_3->siblings(TRUE);
		
		$this
			->assert_count($node_3_siblings, 5)
			->assert_equal($node_3_siblings[2]->id, $new->id)
			->assert_true($node_3->verify_tree());
	}

	
	function test_delete()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		$node_3->delete();
		
		$root = $node_3->root();
		$root_children = $root->children();
		
		$this
			->assert_count($root_children, 3)
			->assert_not_equal($root_children[1]->id, 3)
			->assert_true($node_3->verify_tree());
	}

	function test_move_to_first_child()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->move_to_first_child(3))
			->assert_equal($node_3->move_to_first_child(6), $node_3);
		
		// Load node 6 to check move worked
		$node_6 = Sprig::factory('mptt_test', array('id' => 6))->load();
		$node_6_children = $node_6->children();
		$root = $node_6->root();
		$root_children = $root->children();
		
		$this
			->assert_count($node_6_children, 4)
			->assert_equal($node_6_children[0]->id, 3)
			->assert_count($root_children, 3)
			->assert_not_equal($root_children[1]->id, 3)
			->assert_true($node_3->verify_tree());
			
	}

	function test_move_to_last_child()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->move_to_last_child(3))
			->assert_equal($node_3->move_to_last_child(6), $node_3);
		
		// Load node 6 to check move worked
		$node_6 = Sprig::factory('mptt_test', array('id' => 6))->load();
		$node_6_children = $node_6->children();
		$root = $node_6->root();
		$root_children = $root->children();

		$this
			->assert_count($node_6_children, 4)
			->assert_equal($node_6_children[3]->id, 3)
			->assert_count($root_children, 3)
			->assert_not_equal($root_children[1]->id, 3)
			->assert_true($node_3->verify_tree());
	}

	function test_move_to_prev_sibling()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->move_to_prev_sibling(3))
			->assert_equal($node_3->move_to_prev_sibling(8), $node_3);
		
		// Load node 8 to check move worked
		$node_8 = Sprig::factory('mptt_test', array('id' => 8))->load();
		$node_8_siblings = $node_8->siblings(TRUE);
		$root = $node_8->root();
		$root_children = $root->children();

		$this
			->assert_count($node_8_siblings, 4)
			->assert_equal($node_8_siblings[1]->id, 3)
			->assert_count($root_children, 3)
			->assert_not_equal($root_children[1]->id, 3)
			->assert_true($node_3->verify_tree());
	}
	
	function test_move_to_next_sibling()
	{
		$node_3 = Sprig::factory('mptt_test', array('id' => 3))->load();
		
		$this
			->assert_false($node_3->move_to_next_sibling(3))
			->assert_equal($node_3->move_to_next_sibling(8), $node_3);
		
		// Load node 8 to check move worked
		$node_8 = Sprig::factory('mptt_test', array('id' => 8))->load();
		$node_8_siblings = $node_8->siblings(TRUE);
		$root = $node_8->root();
		$root_children = $root->children();

		$this
			->assert_count($node_8_siblings, 4)
			->assert_equal($node_8_siblings[2]->id, 3)
			->assert_count($root_children, 3)
			->assert_not_equal($root_children[1]->id, 3)
			->assert_true($node_3->verify_tree());
	}

	function test_first_child()
	{
		$node_6 = Sprig::factory('mptt_test', array('id' => 6))->load();
		$first_child = $node_6->first_child;
		
		$this
			->assert_equal($first_child->id, 7);
	}

	function test_last_child()
	{
		$node_6 = Sprig::factory('mptt_test', array('id' => 6))->load();
		$last_child = $node_6->last_child;
		
		$this
			->assert_equal($last_child->id, 11);
	}

} // End Sprig_MPTT