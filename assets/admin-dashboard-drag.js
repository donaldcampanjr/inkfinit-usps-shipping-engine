(function($){
  'use strict';
  $(function(){
    var $container = $('#dashboard-widgets .meta-box-sortables');
    if ($container.length) {
      $container.sortable({
        handle: '.hndle',
        connectWith: '.meta-box-sortables',
        placeholder: 'dashboard-sortable-placeholder',
        update: function(event, ui) {
          var order = [];
          $container.each(function(i, el){
            var ids = $(el).children('.postbox').map(function(){
              return this.id;
            }).get();
            order.push(ids);
          });
          localStorage.setItem('wtcc_dashboard_order', JSON.stringify(order));
        }
      });
      // Restore order
      var saved = localStorage.getItem('wtcc_dashboard_order');
      if (saved) {
        try {
          var order = JSON.parse(saved);
          $container.each(function(i, el){
            if(order[i]){
              order[i].forEach(function(id){
                var $el = $('#' + id);
                if($el.length){
                  $(el).append($el);
                }
              });
            }
          });
        } catch(e){}
      }
    }
  });
})(jQuery);
