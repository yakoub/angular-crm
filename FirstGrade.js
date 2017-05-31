layout.views.FirstGrade = function(container,data,RequestData) {
    var self = this;
    this.container = container;
    this.data = data;
    
    var tabId = this.container.data('tabid');
    var title = FirstGradeTranslations['first_grade_admin'];
    layout.tabs.setTitle(tabId,title);

    this.container.html('<request-list> </request-list>');

    angular.module('firstGrade', []);

    angular.module('firstGrade').component('requestList', {
      templateUrl: 'views/first-grade-1.html',
      controller: ['$http', '$scope', '$templateCache', FirstGrade]
    });

    angular.bootstrap(this.container.get(0), ['firstGrade']);
};


layout.views.FirstGradeBrochure = function(container,data,RequestData) {
  var self = this;
  this.container = container;
  this.data = data;
  
  var tabId = this.container.data('tabid');
  var title = FirstGradeTranslations['first_grade_brochure_admin'];
  layout.tabs.setTitle(tabId,title);

  this.container.html('<request-list> </request-list>');

  angular.module('firstGradeBrochure', []);

  angular.module('firstGradeBrochure').component('requestList', {
    templateUrl: 'views/first-grade-brochure.html',
    controller: ['$http', '$scope', '$templateCache', FirstGradeBrochure]
  });

  angular.bootstrap(this.container.get(0), ['firstGradeBrochure']);
};

function FirstGrade($http, $scope, $templateCache) {
  $templateCache.removeAll();
  $scope.$on('$includeContentError', function(e,s) {
    console.log(s);
  });

  var self = this;
  
  this.http = $http;
  this.loadPage(0);

  this.t = FirstGradeTranslations;
  this.pager = {};
  this.filter = {
    email: '',
    uploaded: '',
    done: '',
    child: '',
    notified: ''
  };
  this.filterRows = function(event) {
    self.loadPage(0);
  };

  this.form_id = 'video';

  this.selected_id = null;
  this.selectedId = function() {
    return this.selected_id;
  }

  this.formMessage = '';

  this.editRow = function(event) {
    self._editRow(event);
  };
  this.openPage = function(event) {
    self._openPage(event);
  }

  this.saveRequest = function(event) {
    self._saveRequest(event);
  };
}

FirstGrade.prototype.listConfig = function() {
  var list_config = {};
  list_config.params = {op: 'list_videos'};
  for (f in this.filter) {
    if (this.filter[f]) {
      list_config.params[f] = this.filter[f];
    }
  }
  return list_config;
}

function FirstGradeBrochure($http, $scope, $templateCache) {
  FirstGrade.call(this, $http, $scope, $templateCache);

  this.form_id = 'brochure';
}

FirstGradeBrochure.prototype = Object.create(FirstGrade.prototype);

FirstGradeBrochure.prototype.listConfig = function() {
  list_config = FirstGrade.prototype.listConfig.call(this); 
  list_config.params.op = 'list_brochures';
  return list_config;
};

FirstGrade.prototype._openPage = function(event) {
  var target = event.target;
  if (target.tagName != 'LI') {
    target = $(event.target).parentsUntil('ul', 'li').get(0);
  }
  var page = target.dataset.page;
  this.loadPage(page);
};

FirstGrade.prototype.loadPage = function(page) {
  var self = this;
  var config = this.listConfig();
  config.params.page = page;
  this.http.get('first_grade.php', config).then(function(response) {
    self.requests = response.data.requests;
    self.pager.current_page = response.data.page;
    var pages = response.data.pages;
    
    var first_page = self.pager.current_page - 3;
    var last_page = self.pager.current_page + 3;
    if (last_page > pages) {
      last_page = pages;
    }
    if (first_page < 0) {
      first_page = 0;
    }

    self.pager.pages = [];
    for (var i = first_page; i < last_page; i++) {
      self.pager.pages[i] = (i == self.pager.current_page);
    }
    self.pager.total = pages;
    self.pager.first_page = (first_page > 0);
    self.pager.last_page = (last_page + 1 < pages);
  });
};

FirstGrade.prototype._editRow = function(event) {
  this.selected_id = $(event.target).parentsUntil('table', 'tr').data('id');
  var top = event.pageY + 20;
  $('form.first-grade-request').css('top', top + 'px');
  this.request = this.requests[this.selected_id]; 
  this.request.new_notification = false;
};

FirstGrade.prototype.cancelRow = function() {
  this.selected_id = null;
  this.request = null;
};

FirstGrade.prototype._saveRequest = function(event) {
  var config = {};
  config.params = {op: 'save'};

  var self = this;
  this.request.form_id = this.form_id;
  if (this.request.new_notification) {
    this.request.notified = null;
  }
  this.http.post('first_grade.php', this.request, config).then(
    function success(response) {
      console.log(response);
      self.request.notified = response.data.notified;
      self.formMessage = 'Request saved';
    },
    function fail(response) {
      console.log(response);
      self.formMessage = 'Request failed';
    }
  );
};

var FirstGradeTranslations = {
  Admin : 'ניהול',
  Requester: 'שם הורה',
  brochure_sent: 'חוברת נשלחה',
  video_done: 'הוכן סרטון',
  video_uploaded: 'הועלה סרטון',
  request_newsletter: 'בקשה ל רשימת תפוצה',
  go_live_url: 'כתובת גו-לייב',
  page_url: 'כתובת דף סרטון',
  yes: 'כן',
  no: 'לא',
  notified: 'נשלח מייל',
  Info : 'מידע',
  parent_name : 'שם הורה',
  Mail : 'מייל',
  child_name : 'שם ילד',
  Gender : 'בן/בת',
  Boy : 'בן',
  Girl : 'בת',
  Image : 'תמונה',
  Greeting : 'ברכה',
  Save : 'שמירה',
  Cancel : 'ביטול',
  first_grade_admin: 'ניהול כיתה א',
  first_grade_brochure_admin: 'ניהול חוברת כיתה א',
  Address: 'כתובת',
  Phone: 'טלפון',
  client_notified_at: 'נשלח מייל בתאריך',
  client_not_notified: 'לא נשלח מייל',
  client_new_notification: 'שליחת מייל חדש',
  Filter: 'סינון',
};
