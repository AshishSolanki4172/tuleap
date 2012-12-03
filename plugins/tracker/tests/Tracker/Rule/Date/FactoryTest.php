<?php
/**
  * Copyright (c) Enalean, 2012. All rights reserved
  *
  * This file is a part of Tuleap.
  *
  * Tuleap is free software; you can redistribute it and/or modify
  * it under the terms of the GNU General Public License as published by
  * the Free Software Foundation; either version 2 of the License, or
  * (at your option) any later version.
  *
  * Tuleap is distributed in the hope that it will be useful,
  * but WITHOUT ANY WARRANTY; without even the implied warranty of
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  * GNU General Public License for more details.
  *
  * You should have received a copy of the GNU General Public License
  * along with Tuleap. If not, see <http://www.gnu.org/licenses/
  */

require_once dirname(__FILE__).'/../../../builders/all.php';
require_once dirname(__FILE__).'/../../../../include/constants.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date/Dao.class.php';
require_once dirname(__FILE__).'/../../../../include/Tracker/Rule/Date/Factory.class.php';

class Tracker_Rule_Date_FactoryTest extends TuleapTestCase {

    /**
     * @var Tracker_Rule_Date_Dao
     */
    protected $date_rule_dao;

    /**
     *
     * @var Tracker_Rule_Date_Factory
     */
    protected $date_rule_factory;

    public function setUp() {
        parent::setUp();

        $this->date_rule_dao = mock('Tracker_Rule_Date_Dao');
        $this->source_field = mock('Tracker_FormElement_Field_Date');
        stub($this->source_field)->getId()->returns(46345);

        $this->target_field = mock('Tracker_FormElement_Field_Date');
        stub($this->target_field)->getId()->returns(465);

        $element_factory = mock('Tracker_FormElementFactory');
        stub($element_factory)->getFormElementById(46345)->returns($this->source_field);
        stub($element_factory)->getFormElementById(465)->returns($this->target_field);

        $this->date_rule_factory = new Tracker_Rule_Date_Factory($this->date_rule_dao, $element_factory);
    }

    public function testCreateRuleDateGeneratesANewObjectThatContainsAllValuesPassed() {
        stub($this->date_rule_dao)->insert()->returns(20);

        $source_field_id = 46345;
        $target_field_id = 465;
        $tracker_id      = 999;
        $comparator      = Tracker_Rule_Date::COMPARATOR_GREATER_THAN;

        $date_rule = $this->date_rule_factory
                ->create($source_field_id, $target_field_id, $tracker_id, $comparator);

        $this->assertIsA($date_rule, 'Tracker_Rule_Date');
        $this->assertEqual($date_rule->getTrackerId(), $tracker_id);
        $this->assertEqual($date_rule->getTargetFieldId(), $target_field_id);
        $this->assertEqual($date_rule->getSourceFieldId(), $source_field_id);
        $this->assertEqual($date_rule->getComparator(), $comparator);
        $this->assertEqual($date_rule->getId(), 20);
    }

    public function testSearchByIdReturnsNullIfNoEntryIsFoundByTheDao() {
        stub($this->date_rule_dao)->searchById()->returnsEmptyDar();
        $date_rule = $this->date_rule_factory
                ->searchById(20);

        $this->assertNull($date_rule);
    }

    public function testSearchByIdReturnsANewObjectIfOneEntryIsFoundByTheDao() {
        $data = array(
            'id'                => 20,
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 999,
        );

        stub($this->date_rule_dao)->searchById(20)->returnsDar($data);
        $date_rule = $this->date_rule_factory
                ->searchById(20);

        $this->assertNotNull($date_rule);
    }

    public function testSearchByTrackerIdReturnsNullIfNoEntryIsFoundByTheDao() {
        stub($this->date_rule_dao)->searchByTrackerId()->returnsEmptyDar();
        $date_rule = $this->date_rule_factory
                ->searchByTrackerId(999);

        $this->assertTrue(is_array($date_rule));
        $this->assertCount($date_rule, 0);
    }


    public function testSearchByTrackerIdReturnsAnArrayOfASingleObjectIfOneEntryIsFoundByTheDao() {
        $data = array(
            'comparator'        => Tracker_Rule_Date::COMPARATOR_LESS_THAN_OR_EQUALS,
            'source_field_id'   => 46345,
            'target_field_id'   => 465,
            'tracker_id'        => 999,
            'id'                => 20
        );

        stub($this->date_rule_dao)->searchByTrackerId()->returnsDar($data);
        $date_rules = $this->date_rule_factory
                ->searchByTrackerId(999);

        $this->assertNotNull($date_rules);
        $this->assertIsA($date_rules, 'array');
        $this->assertCount($date_rules, 1);

        $rule = $date_rules[0];
        $obtained_source_field = $rule->getSourceField();
        $obtained_target_field = $rule->getTargetField();

        $this->assertEqual($obtained_source_field, $this->source_field);
        $this->assertEqual($obtained_target_field, $this->target_field);
        $this->assertEqual($rule->getId(), 20);

    }

    public function itDelegatesDeletionToDao() {
        $tracker_id = '123';
        $rule_id    = '456';
        expect($this->date_rule_dao)->deleteById($tracker_id, $rule_id)->once();
        $this->date_rule_factory->deleteById($tracker_id, $rule_id);
    }
    
    public function testDuplicateDoesNotInsertWhenNoRulesExist() {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;
        $field_mapping   = array(
            array(
                'from'  => 123,
                'to'    => 888
            ),
            array(
                'from'  => 456,
                'to'    => 999
            ),
        );
        
        $db_data = false;
        
        $dao = mock('Tracker_Rule_Date_Dao');
        stub($dao)->searchByTrackerId()->returnsDar($db_data);
        stub($dao)->insert()->never();
        $form_factory = mock('Tracker_FormElementFactory');
        
        $factory = new Tracker_Rule_Date_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping); 
    }
    
    public function testDuplicateInsertsANewRule() {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;
        
        $field_mapping   = array(
            array(
                'from'  => 123,
                'to'    => 888
            ),
            array(
                'from'  => 456,
                'to'    => 999
            ),
        );
        
        $db_data = array(
            'source_field_id' => 123,
            'target_field_id' => 456,
            'comparator'      => Tracker_Rule_Date::COMPARATOR_LESS_THAN,
        );
        
        $dao = mock('Tracker_Rule_Date_Dao');
        stub($dao)->searchByTrackerId()->returnsDar($db_data);
        stub($dao)->insert($to_tracker_id, 888, 999, Tracker_Rule_Date::COMPARATOR_LESS_THAN)->once();
        $form_factory = mock('Tracker_FormElementFactory');
        
        $factory = new Tracker_Rule_Date_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping); 
    }
    
    public function testDuplicateInsertsMultipleRules() {
        $from_tracker_id = 56;
        $to_tracker_id   = 789;
        
        $field_mapping   = array(
            array(
                'from'  => 111,
                'to'    => 555
            ),
            array(
                'from'  => 222,
                'to'    => 666
            ),
            array(
                'from'  => 333,
                'to'    => 777
            ),
            array(
                'from'  => 444,
                'to'    => 888
            ),
        );
        
        $db_data1 = array(
            'source_field_id' => 111,
            'target_field_id' => 222,
            'comparator'      => Tracker_Rule_Date::COMPARATOR_LESS_THAN,
        );
        $db_data2 = array(
            'source_field_id' => 333,
            'target_field_id' => 444,
            'comparator'      => Tracker_Rule_Date::COMPARATOR_LESS_THAN,
        );
        
        $dao = mock('Tracker_Rule_Date_Dao');
        stub($dao)->searchByTrackerId()->returnsDar($db_data1, $db_data2);
        stub($dao)->insert($to_tracker_id, 555, 666, Tracker_Rule_Date::COMPARATOR_LESS_THAN)->at(0);
        stub($dao)->insert($to_tracker_id, 777, 888, Tracker_Rule_Date::COMPARATOR_LESS_THAN)->at(1);
        $form_factory = mock('Tracker_FormElementFactory');
        
        $factory = new Tracker_Rule_Date_Factory($dao, $form_factory);
        $factory->duplicate($from_tracker_id, $to_tracker_id, $field_mapping); 
    }
}
?>
