<?php
if (!(class_exists("AuthStatus"))){
  class AuthStatus  {
      
      const Error      = 0;
      const Busy       = 1;

      const LoginSuccess = 11;
      const LoginMustRegister = 12;

      const LinkSuccess = 21;
      const LinkInUse   = 22;
  }
}
?>
