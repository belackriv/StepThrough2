'use strict';

import Backbone from 'backbone';
import Radio from 'backbone.radio';

const proto = Backbone.Relational.Model.prototype;

export default Backbone.Relational.Model.extend({
  //baseUrl: '/~belac/step-inventory/app_dev.php',
  baseUrl: '',
  fetch(options){
    Radio.channel('app').trigger('request:started');
    return proto.fetch.call(this, options).always(()=>{
      Radio.channel('app').trigger('request:finished');
      if(this.has('isSynced')){
        this.set('isSynced', true);
      }
    });
  },
  save(options){
    Radio.channel('app').trigger('request:started');
    return proto.save.call(this, options).always(()=>{
      Radio.channel('app').trigger('request:finished');
      if(this.has('isSynced')){
        this.set('isSynced', true);
      }
    });
  },
  getValueFromPath(path){
    var pathArray = path.split('.');
    var currentModel = this;
    for(let pathPart of pathArray){
      currentModel = currentModel.get(pathPart);
      if(typeof currentModel === 'undefined'){
        throw 'Path part "'+pathPart+'" undefined!';
      }
      if(!Backbone.Relational.Model || !(currentModel instanceof Backbone.Relational.Model) ){
        break;
      }
    }
    return currentModel;
  },
});