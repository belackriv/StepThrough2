"use strict";

import Radio from 'backbone.radio';
import Marionette from 'marionette';

export default Marionette.View.extend({
  initialize(){
    this.listenTo(Radio.channel('app'), 'route:changed', this.render);
  },
  getTemplate(){
    return this.options.template;
  }
});