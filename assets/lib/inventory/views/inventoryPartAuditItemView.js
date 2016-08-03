'use strict';

import viewTpl from './inventoryPartAuditItemView.hbs!';
import Radio from 'backbone.radio';
import Marionette from 'marionette';
import InventoryPartAuditView from './inventoryPartAuditView.js';

export default Marionette.View.extend({
  template: viewTpl,
  tagName: 'tr',
  ui:{
    'editButton': 'button[name="edit"]',
    'deleteButton': 'button[name="delete"]'
  },
  events:{
    'click @ui.editButton': 'edit',
    'click @ui.deleteButton': 'delete'
  },
  edit(event){
    event.preventDefault();
    var options = {
      title: 'Add Part Count',
      width: '400px'
    };
    let view = new InventoryPartAuditView({
      model: this.model
    });
    Radio.channel('dialog').trigger('close');
    Radio.channel('dialog').trigger('open', view, options);
  },
  delete(event){
    event.preventDefault();
    if(this.ui.deleteButton.data('confirm')){
      this.model.destroy();
    }else{
      this.ui.deleteButton.text('Confirm?');
      this.ui.deleteButton.data('confirm', true);
    }
  }
});
