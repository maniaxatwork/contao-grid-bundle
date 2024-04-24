/* define col-classes for each viewport */
var arrClassesXs = JSON.parse('["col-xs-1","col-xs-2","col-xs-3","col-xs-4","col-xs-5","col-xs-6","col-xs-7","col-xs-8","col-xs-9","col-xs-10","col-xs-11","col-xs-12"]');
var arrClassesSm = JSON.parse('["col-sm-1","col-sm-2","col-sm-3","col-sm-4","col-sm-5","col-sm-6","col-sm-7","col-sm-8","col-sm-9","col-sm-10","col-sm-11","col-sm-12"]');
var arrClassesMd = JSON.parse('["col-md-1","col-md-2","col-md-3","col-md-4","col-md-5","col-md-6","col-md-7","col-md-8","col-md-9","col-md-10","col-md-11","col-md-12"]');
var arrClassesLg = JSON.parse('["col-lg-1","col-lg-2","col-lg-3","col-lg-4","col-lg-5","col-lg-6","col-lg-7","col-lg-8","col-lg-9","col-lg-10","col-lg-11","col-lg-12"]');
var arrClassesXl = JSON.parse('["col-xl-1","col-xl-2","col-xl-3","col-xl-4","col-xl-5","col-xl-6","col-xl-7","col-xl-8","col-xl-9","col-xl-10","col-xl-11","col-xl-12"]');

/* get buttons for grid-size and viewport-size */
var gridButtons = document.querySelectorAll(".grid-buttons .btn");
var viewportButtons = document.querySelectorAll("#viewport_panel .btn");

/* add eventlistener to each grid-button */
gridButtons.forEach((btn) => {

   btn.addEventListener("click", function(){
      /* vars */
      let viewportSize = btn.closest("li.grid").dataset.viewport;
      let action = btn.dataset.action;
      let col = btn.closest("li[class*='col']");
      let arrClasses = [];

      /* get current viewport */
      switch(viewportSize){
         case 'xs':
            arrClasses = arrClassesXs;
            break;
         case 'sm':
            arrClasses = arrClassesSm;
            break;
         case 'md':
            arrClasses = arrClassesMd;
            break;
         case 'lg':
            arrClasses = arrClassesLg;
            break;
         default:
            arrClasses = arrClassesXl;
      }

      /* get current index in viewport-array for plus/minus-action */
      let index = arrClasses.indexOf(col.dataset.cols);
      let oldIndex = index;

      if (action == "plus" && index < arrClasses.length){
         index++;
      } else if (action == "minus" && index >= 0){
         index--;
      }
      if(index >= arrClasses.length || index < 0){
			return;
		}

      /* exchange col-class from classList */
      let newClass=arrClasses[index];
      col.classList.remove(arrClasses[oldIndex]);
      col.classList.add(newClass);
      col.dataset.cols = newClass;

      /* id from element = id from db */
      var id = btn.closest("li").get('id').replace(/li_/, '');

      /* request call for db-update */
      let req = "id=" + id + "&class=" + newClass + "&oldclass=" + arrClasses[oldIndex] + "&rt=" + Contao.request_token;
		new Request.Contao({'url':"/ajaxcolsave?" + req, 'followRedirects':false}).get();
   })
})

viewportButtons.forEach((viewportBtn) => {

   viewportBtn.addEventListener("click", function(){
      let vp = viewportBtn.dataset.viewport;
      document.querySelector('#viewport_panel .btn.active').classList.remove('active');
      viewportBtn.classList.add('active');

      let grids = document.querySelectorAll('li.grid');
      grids.forEach((grid) => {
         grid.dataset.viewport = vp;
      })

      var columns = Array.from(document.querySelectorAll("li[data-cols*='col']"));

      let output = columns.flatMap((column, idx) => {
         let list = column.classList.value.split(' ');
         let matches = list.filter(cls => cls.toLowerCase().includes(vp));
         let defaultClass = list.filter(cls => cls.toLowerCase().includes("col-"));

         if (matches.length) {
            column.dataset.cols = matches[0];
         }else{
            const regex = /(?:col-)([0-9]+)/;
            let number = regex.exec(column.classList);
            if(number != null && number.length > 1){
               column.dataset.cols = "col-" + vp + "-" + number[1];
           }
         }
       })
   })
})

document.querySelector('#viewport_panel .btn.active').click();
