'use strict';

import _ from 'underscore';
import $ from 'jquery';
import 'jquery-ui';
import 'select2';
import 'select2/css/select2.min.css!';
import Backbone from 'backbone';
import Radio from 'backbone.radio';
import Marionette from 'marionette';
import Syphon from 'backbone.syphon';
import AppBehaviors from 'lib/common/behaviors/behaviors.js';
import ErrorMessageView from 'lib/common/views/errorMessageView.js';

if (window.__agent) {
  window.__agent.start(Backbone, Marionette);
}

var handleAjaxError = function(jqXHR,textStatus,errorThrown,dialogOptions){
  let model = null;
  try{
    let errorObj = jqXHR.responseJSON?jqXHR.responseJSON:JSON.parse(jqXHR.responseText);
    model = new Backbone.Model(errorObj.error);
  }catch(e){
    model = new Backbone.Model();
  }
  let view = new ErrorMessageView({
    model: model
  });
  let options = _.extend({
    title: 'HTTP Error',
    width: '80%'
  }, dialogOptions);
  Radio.channel('dialog').trigger('open', view, options);
};

Backbone.ajax = function() {
    // Invoke $.ajaxSetup in the context of Backbone.$
    Backbone.$.ajaxSetup.call(Backbone.$, {
        statusCode: {
            400(jqXHR,textStatus,errorThrown){
                //400  -- Show Error in Dialog
                handleAjaxError(jqXHR,textStatus,errorThrown);
            },
            401(){
                // Redirect the to the login page.
                window.location = '/login';
            },
            403(jqXHR,textStatus,errorThrown){
                // 403 -- Access denied
                var options = {
                    title: 'Access Denied',
                    width: '80%'
                };
                handleAjaxError(jqXHR,textStatus,errorThrown, options);
            },
            404(jqXHR,textStatus,errorThrown){
                //405  -- Show Error in Dialog
                handleAjaxError(jqXHR,textStatus,errorThrown);
            },
            405(jqXHR,textStatus,errorThrown){
              //405  -- Show Error in Dialog
              handleAjaxError(jqXHR,textStatus,errorThrown);
            },
            409(jqXHR,textStatus,errorThrown){
                //405  -- Show Error in Dialog
                handleAjaxError(jqXHR,textStatus,errorThrown);
            },
            415(jqXHR,textStatus,errorThrown){
                //415  -- Show Error in Dialog
                handleAjaxError(jqXHR,textStatus,errorThrown);
            },
            422(jqXHR,textStatus,errorThrown){
                //400  -- Show Error in Dialog
                handleAjaxError(jqXHR,textStatus,errorThrown);
            },
            500(jqXHR,textStatus,errorThrown){
              //500  -- Show Error in Dialog
              handleAjaxError(jqXHR,textStatus,errorThrown);

            }
    }});
    return Backbone.$.ajax.apply(Backbone.$, arguments);
};

Marionette.Behaviors.behaviorsLookup = function() {
    return AppBehaviors;
};

var nullForBlankReaderSet = new Syphon.InputReaderSet();
nullForBlankReaderSet.registerDefault(function($el){
    return $el.val()===''?null:$el.val();
});
Syphon.InputReaders = nullForBlankReaderSet;

//select2 fix in jquery dialogs
if ($.ui && $.ui.dialog && $.ui.dialog.prototype._allowInteraction) {
    var ui_dialog_interaction = $.ui.dialog.prototype._allowInteraction;
    $.ui.dialog.prototype._allowInteraction = function(e) {
        if ($(e.target).closest('.select2-dropdown').length) return true;
        return ui_dialog_interaction.apply(this, arguments);
    };
}

String.prototype.capitalizeFirstLetter = function() {
	return this.charAt(0).toUpperCase() + this.slice(1);
};




export default {
  castAsType(variable, type){

    switch(type){
      case 'int':
      case 'integer':
        return Number(variable);
      case 'bool':
      case 'boolean':
        if(variable === 'false'){
          return false;
        }
        return (variable)?true:false;
      default:
        return variable;
    }

  }
}