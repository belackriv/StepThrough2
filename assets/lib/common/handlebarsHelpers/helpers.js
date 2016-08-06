'use strict';

import _ from 'underscore';
import Handlebars from 'handlebars/handlebars.runtime.js';
import Moment from 'moment';
import Radio from 'backbone.radio';
import BaseUrlBaseModel from 'lib/common/models/baseUrlBaseModel.js';

Handlebars.registerHelper('titleCase', function(str, options) {
  var newstr = (str+'').split(" ");
  for(var i=0;i<newstr.length;i++){
    var copy = newstr[i].substring(1).toLowerCase();
    newstr[i] = newstr[i][0].toUpperCase() + copy;
  }
   newstr = newstr.join(" ");
   return newstr;
});

Handlebars.registerHelper('upperCase', function(str, options) {
   return (str+'').toUpperCase();
});


Handlebars.registerHelper('moment', function(data, options) {
  var format = options.hash.format?options.hash.format:'h:mm A, ddd MMM D YYYY';
  return Moment(data).format(format);
});


Handlebars.registerHelper('log', function(data, options) {
  console.log(data);
  return '';
});

Handlebars.registerHelper('ifIsRouteActive', function(route, options) {
  route = (route[0]=='/')?route.slice(1):route;
  let routeRegExp = new RegExp('^'+route+'(/\\d+)?$');
  let currentRoute = Radio.channel('app').request('currentRoute');
  if(routeRegExp.test(currentRoute)){
    return options.fn(this);
  } else {
    return options.inverse(this);
  }
});

Handlebars.registerHelper('isGrantedRole', function (role, options) {
  let myself =  Radio.channel('data').request('myself');
  return (myself.isGrantedRole(role, myself) )?options.fn(this):options.inverse(this);
});

Handlebars.registerHelper('isMyself', function (user, options) {
  let myself =  Radio.channel('data').request('myself');
  return (myself.id === user.id)?options.fn(this):options.inverse(this);
});

Handlebars.registerHelper('concat', function(options) {
  let str = '';
  _.each(arguments, (arg)=>{
    if(typeof arg === 'string'){
      str += arg;
    }
  });
  return str;
});

Handlebars.registerHelper('truncate', function(data, options) {
  let maxLength = options.hash.maxLength?parseInt(options.hash.maxLength):30;
  let str = ''+data;
  if(str.length > maxLength && maxLength > 3){
    return str.substr(0, maxLength - 3)+'...';
  }else{
    return str.substr(0, maxLength);
  }
});

Handlebars.registerHelper('translate', function(term, dict, options) {
  return dict[term];
});

Handlebars.registerHelper('baseUrl', function (options) {
   let str = BaseUrlBaseModel.prototype.baseUrl+'';
  _.each(arguments, (arg)=>{
    if(typeof arg === 'string'){
      str += arg;
    }
  });
  return str;
});

Handlebars.registerHelper('ifCond', function (v1, operator, v2, options) {
  switch (operator) {
    case '==':
      return (v1 == v2) ? options.fn(this) : options.inverse(this);
    case '===':
      return (v1 === v2) ? options.fn(this) : options.inverse(this);
    case '<':
      return (v1 < v2) ? options.fn(this) : options.inverse(this);
    case '<=':
      return (v1 <= v2) ? options.fn(this) : options.inverse(this);
    case '>':
      return (v1 > v2) ? options.fn(this) : options.inverse(this);
    case '>=':
      return (v1 >= v2) ? options.fn(this) : options.inverse(this);
    case '&&':
      return (v1 && v2) ? options.fn(this) : options.inverse(this);
    case '||':
      return (v1 || v2) ? options.fn(this) : options.inverse(this);
    default:
      return options.inverse(this);
  }
});