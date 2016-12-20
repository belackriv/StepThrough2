"use strict";

import Marionette from 'marionette';
import Radio from 'backbone.radio';

import viewTpl from './accountView.hbs!';

import UserCollection from '../models/userCollection.js';
import PlanCollection from '../models/planCollection.js';
import SubscriptionModel from '../models/subscriptionModel.js';
import AccountOwnerChangeModel from '../models/accountOwnerChangeModel.js';
import AccountSubscriptionChangeModel from '../models/accountSubscriptionChangeModel.js';

import NoChildrenRowView from 'lib/common/views/noChildrenRowView.js';
import AccountChangeItemView from './accountChangeItemView.js';
import BillItemView from './billItemView.js';
import PaymentInfoView from './paymentInfoView.js';

export default Marionette.View.extend({
  initialize(){
    this.setInitialProperties();
  },
  template: viewTpl,
  behaviors: {
    'Stickit': {},
  },
  regions:{
    'changeHistory': {
      el: 'tbody[data-region="changeHistory"]',
      replaceElement: true
    },
    'billingHistory': {
      el: 'tbody[data-region="billingHistory"]',
      replaceElement: true
    }
  },
  ui: {
    'ownerSelect': 'select[name="owner"]',
    'planSelect': 'select[name="plan"]',
    'planDesc': '[data-ui="planDesc"]',
    'changeOwnerButton': 'button[data-ui="changeOwner"]',
    'changeSubscriptionButton': 'button[data-ui="changeSubscription"]',
    'addPaymentInfoButton': 'button[data-ui="addPaymentInfo"]',
  },
  events:{
    'click @ui.changeOwnerButton': 'changeOwner',
    'click @ui.changeSubscriptionButton': 'changeSubscription',
    'click @ui.addPaymentInfoButton': 'openAddPaymentInfoDialog'
  },
  modelEvents:{
    'change:organization': 'setInitialProperties',
    'change:subscription': 'subscriptionChanged'
  },
  bindings: {
    '@ui.ownerSelect': {
      observe: 'owner',
      useBackboneModels: true,
      selectOptions:{
        labelPath: 'attributes.username',
        collection(){
          let collection = Radio.channel('data').request('collection', UserCollection, {fetchAll: true});
          return collection;
        },
        defaultOption: {
          label: 'Choose one...',
          value: null
        }
      }
    },
    '@ui.planSelect': {
      observe: 'subscription',
      useBackboneModels: true,
      onGet(value){
        return value.get('plan');
      },
      onSet(value){
        return SubscriptionModel.findOrCreate({
          plan: value
        });
      },
      selectOptions:{
        labelPath: 'attributes.name',
        collection(){
          let collection = Radio.channel('data').request('collection', PlanCollection, {fetchAll: true});
          return collection;
        },
        defaultOption: {
          label: 'Choose one...',
          value: null
        }
      }
    },
  },
  setInitialProperties(){
    this.initialProperties = {
      owner: this.model.get('owner'),
      subscription: this.model.get('subscription'),
    };
  },
  subscriptionChanged(){
    this.ui.planDesc.text(this.model.get('subscription').get('plan').get('description'));
  },
  onRender(){
    this.subscriptionChanged();
    this.showChildView('changeHistory', new Marionette.CollectionView({
      collection: this.model.get('accountChanges'),
      childView: AccountChangeItemView,
      tagName: 'tbody',
      emptyView: NoChildrenRowView,
      emptyViewOptions:{
        colspan: 6
      }
    }));
    this.showChildView('billingHistory', new Marionette.CollectionView({
      collection: this.model.get('bills'),
      childView: BillItemView,
      tagName: 'tbody',
      emptyView: NoChildrenRowView,
      emptyViewOptions:{
        colspan: 3
      }
    }));
  },
  changeOwner(event){
    event.preventDefault();
    this.disableButtons('changeOwnerButton');
    let accountOwnerChange = AccountOwnerChangeModel.findOrCreate({
      account: this.model,
      oldOwner: this.initialProperties.owner,
      newOwner: this.model.get('owner'),
    });
    accountOwnerChange.save().always(()=>{
      this.enableButtons();
    }).done(()=>{
      this.model.get('accountChanges').add(accountOwnerChange);
    });
  },
  changeSubscription(event){
    event.preventDefault();
    this.disableButtons('changeSubscriptionButton');
    let accountSubscriptionChange = AccountSubscriptionChangeModel.findOrCreate({
      account: this.model,
      oldSubscription: this.initialProperties.subscription,
      newSubscription: this.model.get('subscription'),
    });
    accountSubscriptionChange.save().always(()=>{
      this.enableButtons();
    }).done(()=>{
      this.model.get('accountChanges').add(accountSubscriptionChange);
    });
  },
  openAddPaymentInfoDialog(event){
    event.preventDefault();
    var options = {
      title: 'Add Payment Info',
      width: '400px'
    };
    let view = new PaymentInfoView({
      model: this.model
    });
    Radio.channel('dialog').trigger('close');
    Radio.channel('dialog').trigger('open', view, options);
  },
  disableButtons(loadingButtonName){
    this.ui.changeOwnerButton.addClass('is-disabled').prop('disabled', true);
    this.ui.changeSubscriptionButton.addClass('is-disabled').prop('disabled', true);
    this.ui[loadingButtonName].removeClass('is-disabled').addClass('is-loading');
  },
  enableButtons(){
    this.ui.changeOwnerButton.removeClass('is-disabled is-loading').prop('disabled', false);
    this.ui.changeSubscriptionButton.removeClass('is-disabled is-loading').prop('disabled', false);
  }
});