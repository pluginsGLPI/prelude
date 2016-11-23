var add_criterion = function() {
   var criterion = $('input.criterion:last()');
   criterion
      .clone()
      .removeAttr('required')
      .insertAfter(criterion);
};