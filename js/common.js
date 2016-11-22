var add_criterion = function() {
   var criterion = $('input.criterion:last()');
   console.log('add_criterion'),
   criterion.clone().insertAfter(criterion);
};