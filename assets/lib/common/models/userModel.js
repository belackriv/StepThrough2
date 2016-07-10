'use strict';


import globalNamespace from 'lib/globalNamespace';
import Backbone from 'backbone';
import Radio from 'backbone.radio';
import BaseUrlBaseModel from './baseUrlBaseModel.js';

let Model = BaseUrlBaseModel.extend({
  urlRoot(){
    return this.baseUrl+'/user';
  },
  relations: [{
    type: Backbone.HasOne,
    key: 'defaultDepartment',
    relatedModel: 'DepartmentModel',
    includeInJSON: ['id']
  },{
    type: Backbone.HasOne,
    key: 'currentDepartment',
    relatedModel: 'DepartmentModel',
    includeInJSON: ['id']
  }],
  defaults:{
    username: null,
    email: null,
    firstName: null,
    lastName: null,
    isActive: null,
    defaultDepartment: null,
    currentDepartment: null,
    userRoles: null,
  },
  hasUserRole(userRole){
    return this.get('userRoles').get(userRole)?true:false;
  }
});

globalNamespace.Models.UserModel = Model;

export default Model;