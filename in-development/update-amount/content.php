<div class="row">

<!-- LEFT SIDE -->
<div class="col-xl-6">

<!-- SINGLE UPDATE -->
<div class="card">
<div class="card-body">

<form id="single-form">

<div class="row">

<div class="col-8">
<label>Parent/ Mother Barcode</label>
<input type="text" class="form-control" name="parent_barcode" required>
</div>

<div class="col-4">
<label>Sequence</label>
<input type="number" min="0" class="form-control" name="sequence" required>
</div>

<div class="col-4">
<label>New Amount</label>
<input type="number" step="0.01" class="form-control" name="new_amount" required>
</div>

<div class="col-12 mt-3">
<button class="btn btn-primary">Submit</button>
</div>

</div>

</form>

</div>
</div>


<!-- MULTIPLE UPDATE -->
<div class="card mt-3">
<div class="card-body">

<form id="multi-form">

<div class="row">

<div class="col-8">
<label>Parent/ Mother Barcode</label>
<input type="text" class="form-control" name="parent_barcode2" required>
</div>

<div class="col-2">
<label>From</label>
<input type="number" min="0" id="from" name="from" class="form-control" required>
</div>

<div class="col-2">
<label>To</label>
<input type="number" min="1" id="to" name="to" class="form-control" required>
</div>

<div class="col-4">
<label>New Amount</label>
<input type="number" step="0.01" class="form-control" name="new_amount2" required>
</div>

<div class="col-12 mt-3">
<button class="btn btn-secondary">Submit</button>
</div>

</div>

</form>

</div>
</div>

</div>


<!-- RIGHT SIDE -->
<div class="col-xl-6">

<div class="card">

<div class="card-header d-flex justify-content-between">
<b>Session Updates</b>

<button class="btn btn-danger btn-sm" id="clearList">
Clear List
</button>

</div>

<div class="card-body">

<table class="table table-bordered" id="updatesTable">

<thead>
<tr>
<th>Parent Barcode</th>
<th>Extension/s</th>
<th>Amount Updated</th>
<th>Rows Updated</th>
</tr>
</thead>

<tbody></tbody>

</table>

</div>
</div>

</div>

</div>


<!-- LIBRARIES -->

<!-- <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
 -->


<script>

let table;

$(document).ready(function(){

table = $("#updatesTable").DataTable();

loadSessionUpdates();

});


/* LOAD PREVIOUS SESSION DATA */
function loadSessionUpdates(){

$.get("../config/get_updates.php", function(res){

let data = JSON.parse(res);

data.forEach(function(row){

addRow(row.parent,row.ext,row.amount,row.rows);

});

});

}



/* ADD ROW TO TABLE */
function addRow(parent,ext,amount,rows){

table.row.add([
parent,
ext,
amount,
rows
]).draw(false);

}



/* SINGLE UPDATE */

$("#single-form").submit(function(e){

e.preventDefault();

$.ajax({

url:"../config/singleamountupdate.php",
type:"POST",
data:$(this).serialize(),

success:function(res){

let data = JSON.parse(res);

Swal.fire({
icon:'success',
title:'Updated Successfully',
timer:1500,
showConfirmButton:false
});

addRow(data.parent,data.ext,data.amount,data.rows);

}

});

});



/* MULTIPLE UPDATE */

$("#multi-form").submit(function(e){

e.preventDefault();

let from = parseInt($("#from").val());
let to = parseInt($("#to").val());

if(from >= to){

Swal.fire({
icon:'warning',
title:'Invalid Range',
text:'"From" must be less than "To"'
});

return;
}

$.ajax({

url:"../config/multipleamountupdate.php",
type:"POST",
data:$(this).serialize(),

success:function(res){

let data = JSON.parse(res);

Swal.fire({
icon:'success',
title:'Updated Successfully',
timer:1500,
showConfirmButton:false
});

addRow(data.parent,data.ext,data.amount,data.rows);

}

});

});



/* CLEAR LIST */

$("#clearList").click(function(){

Swal.fire({
title:'Clear list?',
icon:'warning',
showCancelButton:true
}).then((result)=>{

if(result.isConfirmed){

$.get("../config/clear_updates.php",function(){

table.clear().draw();

});

}

});

});

</script>