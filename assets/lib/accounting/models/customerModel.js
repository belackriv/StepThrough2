'use strict';

import globalNamespace from 'lib/globalNamespace.js';
import BackboneRelational from 'backbone.relational';
import BaseUrlBaseModel from 'lib/common/models/baseUrlBaseModel.js';

import 'lib/common/models/contactModel.js';
import 'lib/common/models/addressModel.js';

let Model = BaseUrlBaseModel.extend({
  urlRoot(){
    return this.baseUrl+'/customer';
  },
  importData: {
    type: 'customer',
    properties: [
      { name: 'name', required: true, description: null},
    ]
  },
  relations: [{
    type: BackboneRelational.HasMany,
    key: 'contacts',
    relatedModel: 'ContactModel',
    includeInJSON: false,
    reverseRelation: {
      key: 'customer',
      includeInJSON: ['id'],
    }
  },{
    type: BackboneRelational.HasMany,
    key: 'addresses',
    relatedModel: 'AddressModel',
    includeInJSON: false,
    reverseRelation: {
      key: 'customer',
      includeInJSON: ['id'],
    }
  }],
  defaults: {
    name: null,
    contacts: null,
    addresses: null
  },
});

globalNamespace.Models.CustomerModel = Model;

export default Model;