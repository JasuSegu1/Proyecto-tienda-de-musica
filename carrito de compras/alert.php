<?php

$all_msgs = [
   'success' => $success_msg ?? [],
   'warning' => $warning_msg ?? [],
   'error' => $error_msg ?? [],
   'info' => $info_msg ?? [],
];

foreach ($all_msgs as $type => $messages) {
   foreach ($messages as $msg) {
      echo '<script>swal("'.htmlspecialchars($msg, ENT_QUOTES).'","","'.$type.'");</script>';
   }
}

?>

<script>

function confirm(e, message){
   e.preventDefault();
   
   let form = e.currentTarget.form;
   let btn = e.currentTarget;

   swal({
      title : message,
      icon : 'warning',
      buttons : true,
      dangerMode : true,

   }).then(ok =>{
      if(ok) form.requestSubmit(btn);
   });
   return false;
};

</script>