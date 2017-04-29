'use strict';

import Marionette from 'marionette';
import ChildView from './unitPropertyItemView.js';
import NoChildrenView from 'lib/common/views/noChildrenView.js';

export default Marionette.CollectionView.extend({
  childView: ChildView,
  tagName: 'tbody',
  emptyView: NoChildrenView,
});
