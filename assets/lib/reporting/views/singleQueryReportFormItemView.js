'use strict';

import _ from 'underscore';
import Marionette from 'marionette';
import Handlebars from 'handlebars';
import viewTpl from './singleQueryReportFormItemView.hbs!';

export default Marionette.View.extend({
  getTemplate(){
    if(this.model.get('template')){
  	  return Handlebars.compile(this.model.get('template'));
    }else{
      return viewTpl;
    }
  },
  modelEvents: {
    'change:choices':'addChoices'
  },
  addChoices(){
    if(this.model.get('choices')){
      _.each(this.model.get('choices'), (choice)=>{
        this.$el.find('[name="'+this.model.get('name')+'"]').append('<option value="'+choice.value+'">'+choice.label+'</option>');
      });
    }
    this.$el.find('[name="'+this.model.get('name')+'"]').trigger('change');
  },
  onAttach(){
    const $select2 = this.$el.find('[use_select_2="true"]');
    $select2.select2({
      width: 'resolve',
      dropdownAutoWidth: true
    });
    if($select2.length > 0){
      this.addChoices();
    }
    this.$el.find('input[type="date"]').datepicker().attr('type','text');
  },
  //className:'vsm-single-query-report-form-item'
});