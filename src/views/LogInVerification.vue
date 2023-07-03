<template>
  <ion-page>
    <ion-content>
      <div class="otp-input-fields">
        <input type="text" maxlength="1" />
        <input type="text" maxlength="1" />
        <input type="text" maxlength="1" />
        <input type="text" maxlength="1" />
        <input type="text" maxlength="1" />
        <input type="text" maxlength="1" />
      </div>
    </ion-content>
  </ion-page>
</template>
<script>
import axios from "axios";
import qs from "qs";

export default {
  mounted() {
    const inputs = document.querySelectorAll(".otp-input-fields input");
    console.log(inputs.length);
    inputs.forEach((input, index) => {
      input.dataset.index = index;
      input.addEventListener("focus", clear);
      input.addEventListener("keydown", clear);
      input.addEventListener("paste", onPaste);
      input.addEventListener("keyup", onKeyUp);
    });

    function clear($event) {
      $event.target.value = "";
    }

    function checkNumber(number) {
      return /[0-9]/g.test(number);
    }

    function onPaste($event) {
      const data = $event.clipboardData.getData("text");
      const value = data.replace(/ /g, "").split("");
      if (!value.some((number) => !checkNumber(number))) {
        if (value.length == inputs.length) {
          inputs.forEach((input, index) => {
            input.value = value[index];
            submit();
          });
        }
      } else {
        return;
      }
    }

    function onKeyUp($event) {
      const input = $event.target;
      const value = input.value;
      const fieldIndex = +input.dataset.index;

      if ($event.key == "Backspace" && fieldIndex > 0) {
        input.previousElementSibling.focus();
      }

      if (checkNumber(value)) {
        if (value.length > 0 && fieldIndex < inputs.length - 1) {
          input.nextElementSibling.focus();
        }

        if (input.value != "" && fieldIndex === inputs.length - 1) {
          submit();
        }
      } else {
        clear($event);
      }
    }

    function submit() {
      let otp = "";
      inputs.forEach((input) => {
        otp += input.value;
        input.disabled = true;
      });

      const verification_token = localStorage.getItem("verification_token");
      axios
        .post(
          "https://alex.polan.sk/control-center/verification.php",
          qs.stringify({
            verificationToken: verification_token,
            verificationCode: otp,
          })
        )
        .then((res) => {
          if (res.data.token) {
            localStorage.setItem("token", res.data.token);
            location.href = "/home";
          }else{
           if(confirm("False verifiction code")){
            location.href = "/login";
           }
          }
        });
    }
  },
};
</script>
<style scoped>
.otp-input-fields {
  display: flex;
  flex-direction: row;
  width: 100%;
  height: 100%;
  align-items: center;
  justify-content: center;
}

.otp-input-fields > input {
  width: 4.9rem;
  padding: 1.5rem;
  font-size: 3rem;
  border: none;
  margin: 0 1rem 0 0;
  background-color: #1e1e1e;
  border-radius: 0.5rem;
  color: white;
  text-align: center;
  transition: all 150ms ease-in-out;
}

.otp-input-fields > input:last-child {
  margin: 0 0 0 0;
}

.otp-input-fields > input:focus {
  color: red;
  outline: 0.3rem solid red;
}

.otp-input-fields > input:disabled {
  opacity: 0.3;
}
</style>
