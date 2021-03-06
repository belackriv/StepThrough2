"use strict";

import Marionette from 'marionette';
import Radio from 'backbone.radio';

import viewTpl from './accountView.hbs!';

import UserCollection from '../models/userCollection.js';
import PlanCollection from '../models/planCollection.js';
import SubscriptionModel from '../models/subscriptionModel.js';
import AccountOwnerChangeModel from '../models/accountOwnerChangeModel.js';
import AccountPlanChangeModel from '../models/accountPlanChangeModel.js';

import NoChildrenRowView from 'lib/common/views/noChildrenRowView.js';
import PaymentSourceItemView from './paymentSourceItemView.js';
import CurrentSessionItemView from './currentSessionItemView.js';
import AccountChangeItemView from './accountChangeItemView.js';
import BillItemView from './billItemView.js';

import PaymentInfoView from './paymentInfoView.js';

export default Marionette.View.extend({
  initialize(){
    this.setInitialProperties();
  },
  template: viewTpl,
  className: 'box',
  behaviors: {
    'Stickit': {},
  },
  regions:{
    'paymentSources': {
      el: 'tbody[data-region="paymentSources"]',
      replaceElement: true
    },
    'currentSessions': {
      el: 'tbody[data-region="currentSessions"]',
      replaceElement: true
    },
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
    'changePlanButton': 'button[data-ui="changePlan"]',
    'cancelSubscriptionButton': 'button[data-ui="cancelSubscription"]',
    'startSubscriptionButton': 'button[data-ui="startSubscription"]',
    'addPaymentInfoButton': 'button[data-ui="addPaymentInfo"]',
  },
  events:{
    'click @ui.changeOwnerButton': 'changeOwner',
    'click @ui.changePlanButton': 'changePlan',
    'click @ui.cancelSubscriptionButton': 'cancelSubscription',
    'click @ui.startSubscriptionButton': 'startSubscription',
    'click @ui.addPaymentInfoButton': 'openAddPaymentInfoDialog'
  },
  modelEvents:{
    'change:organization': 'setInitialProperties',
    'change:newPlan': 'planChanged'
  },
  childEvents: {
    'session:destroyed': 'render'
  },
  bindings: {
    '@ui.ownerSelect': {
      observe: 'owner',
      useBackboneModels: true,
      selectOptions:{
        labelPath: 'attributes.username',
        collection(){
          return this.model.get('ownerSelections');
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
        if(value){
          return value.get('plan');
        }else{
          return null;
        }
      },
      updateModel(value){
        this.model.set('newPlan', value);
        return false;
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
  getPlanInfo(planModel){
    let price = new Number(planModel.get('amount')/100);
    return '<strong>'+price.toLocaleString('en-US',{ style: 'currency', currency: 'USD'})+'</strong>: '+planModel.get('description');
  },
  planChanged(){
    this.ui.planDesc.html(this.getPlanInfo(this.model.get('newPlan')));
  },
  onRender(){
    if(this.model.get('subscription') && this.model.get('subscription').get('plan')){
      this.ui.planDesc.html(this.getPlanInfo(this.model.get('subscription').get('plan')));
    }
    this.showChildView('paymentSources', new Marionette.CollectionView({
      collection: this.model.get('paymentSources'),
      childView: PaymentSourceItemView,
      tagName: 'tbody',
      emptyView: NoChildrenRowView,
      emptyViewOptions:{
        colspan: 5
      }
    }));
    this.showChildView('currentSessions', new Marionette.CollectionView({
      collection: this.model.get('currentSessions'),
      childView: CurrentSessionItemView,
      childViewOptions:{
        parentCollection: this.model.get('currentSessions')
      },
      tagName: 'tbody',
      emptyView: NoChildrenRowView,
      emptyViewOptions:{
        colspan: 6
      },
      filter(session){
        return (session.get('forUsername') && session.get('startedAt') && session.get('updatedAt'));
      }
    }));
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
  changePlan(event){
    event.preventDefault();
    this.disableButtons('changePlanButton');
    let accountPlanChange = AccountPlanChangeModel.findOrCreate({
      account: this.model,
      oldPlan: this.model.get('subscription').get('plan'),
      newPlan: this.model.get('newPlan'),
    });
    accountPlanChange.save().always(()=>{
      this.enableButtons();
    }).done(()=>{
      this.model.get('accountChanges').add(accountPlanChange);
    });
  },
  cancelSubscription(event){
    event.preventDefault();
    if(this.ui.cancelSubscriptionButton.data('confirmed') === true){
      this.ui.cancelSubscriptionButton.prop('disabled', true);
      this.disableButtons('cancelSubscriptionButton');
      if(this.model.get('subscription')){
        this.model.get('subscription').cancel().then(()=>{
          this.model.set('subscription', null);
          this.render();
        });
      }
    }else{
      this.ui.cancelSubscriptionButton.data('confirmed', true).text('Confirm?');
    }
  },
  startSubscription(event){
    event.preventDefault();
    if(this.ui.startSubscriptionButton.data('confirmed') === true){
      this.ui.startSubscriptionButton.prop('disabled', true);
      this.disableButtons('startSubscriptionButton');
      if(this.model.get('subscription') === null && this.model.get('newPlan')){
        let subscription = SubscriptionModel.findOrCreate({
          plan: this.model.get('newPlan'),
          account: this.model
        });
        subscription.save().then(()=>{
          this.model.set('subscription', subscription);
          this.render();
        });
      }
    }else{
      this.ui.startSubscriptionButton.data('confirmed', true).text('Confirm?');
    }
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
    this.ui.changePlanButton.addClass('is-disabled').prop('disabled', true);
    this.ui[loadingButtonName].removeClass('is-disabled').addClass('is-loading');
  },
  enableButtons(){
    this.ui.changeOwnerButton.removeClass('is-disabled is-loading').prop('disabled', false);
    this.ui.changePlanButton.removeClass('is-disabled is-loading').prop('disabled', false);
  }
});