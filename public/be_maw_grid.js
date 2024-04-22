/*
var rows = document.querySelectorAll(".maw_row"),
    row_ends = document.querySelectorAll(".maw_row_end"),
    cols = document.querySelectorAll(".maw_col"),
    col_ends = document.querySelectorAll(".maw_col_end"),
    listItem = document.createElement("ul"),
    listCol = document.createElement("li");

    listItem.classList.add('grid_wrapper_outer');
    listCol.classList.add('grid_wrapper_inner');

rows.forEach((row) => {
    row.closest('li').classList.add('rowStart');
 })

 row_ends.forEach((row_end) => {
    row_end.closest('li').classList.add('rowEnd');
 })

 cols.forEach((col) => {
   let item = col.closest('li');
   item.classList.add('colStart');
   let size = (item.querySelector('.tl_grid_note:not(.maw_col)') !== null) ? item.querySelector('.tl_grid_note:not(.maw_col)').innerText : 'col-12';
   item.classList.add(size);
 })

 col_ends.forEach((col_end) => {
    col_end.closest('li').classList.add('colEnd');
 })

 function hasClass(element, className) {
   if (element && element.className){
      return (element.className).indexOf(className) > -1;
   }
   else{
      return false;
   }
  }

  var listing = document.getElementById('tl_listing');

  var lis = listing.querySelectorAll('li.colStart');

   lis.forEach((li) => {
      let nextLi = li.nextElementSibling;

      let wrapper = document.createElement("div");
      wrapper.classList.add('col');

      if (hasClass(nextLi, 'rowEnd')) return;

      let ids = [],
         counter = 0;
      while(hasClass(nextLi,'colEnd') == false){
         ids[counter] = nextLi.id;
         counter++;
         nextLi = nextLi.nextElementSibling;
      }
      ids[counter] = nextLi.id;

      for(let i = 0; i < ids.length ; i++){
         wrapper.append(document.getElementById(ids[i]));
       }

      li.appendChild(wrapper);
   })

  var lis = listing.querySelectorAll('li.rowStart');

  lis.forEach((li) => {
     let nextLi = li.nextElementSibling;

     let wrapper = document.createElement("div");
     wrapper.classList.add('row');

     let ids = [],
        counter = 0;
     while(hasClass(nextLi,'rowEnd') == false){
        ids[counter] = nextLi.id;
        counter++;
        nextLi = nextLi.nextElementSibling;
     }

     for(let i = 0; i < ids.length ; i++){
        wrapper.append(document.getElementById(ids[i]));
      }

     li.appendChild(wrapper);
  })

*/
var arrClasses = JSON.parse('["col-1","col-2","col-3","col-4","col-5","col-6","col-7","col-8","col-9","col-10","col-11","col-12"]');
var objButtons = document.querySelectorAll(".grid-buttons .btn");

objButtons.forEach((btn) => {

   btn.addEventListener("click", function(){
      let action = btn.dataset.action;
      let col = btn.closest("li[class*='col']");
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

      let newClass=arrClasses[index];
      col.classList.remove(arrClasses[oldIndex]);
      col.classList.add(newClass);
      col.dataset.cols = newClass;

      id = btn.closest("li").get('id').replace(/li_/, '');
      pid = btn.closest("ul[id*='ul_']").get('id').replace(/ul_/, '');
      console.log(pid);
      const xhttp = new XMLHttpRequest();
      xhttp.onreadystatechange = function() {
         if (this.readyState == 4 && this.status == 200) {
           console.log(this.responseText);
         }
       };
      xhttp.open("POST", "/ajaxcolsave", true);
      xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
      xhttp.send("id=" + id + "&class=" + newClass + "&oldclass=" + arrClasses[oldIndex]);

		//req = window.location.search.replace(/id=[0-9]*/, 'id=' + id) + '&act=edit&rt=' + Contao.request_token;
		//href = window.location.href.replace(/\?.*$/, '');
		//new Request.Contao({'url':href + req, 'followRedirects':false}).get();
   })
})
