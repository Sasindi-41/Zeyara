function changeView() {
  var signinbox = document.getElementById ("signin_box");
  var signupbox = document.getElementById("signup_Box");

  signinbox.classList.toggle("d-none");
  signupbox.classList.toggle("d-none");
}

function signload() {
  location.reload();
}
function signup() {
  var fname = document.getElementById("fname");
  var lname = document.getElementById("lname");
  var email = document.getElementById("email");
  var mobile = document.getElementById("mobile");
  var username = document.getElementById("username");
  var password = document.getElementById("password");

  // form to send data through post method

  var f = new FormData();

  f.append("fn", fname.value);
  f.append("ln", lname.value);
  f.append("email", email.value);
  f.append("mobile", mobile.value);
  f.append("username", username.value);
  f.append("password", password.value);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if ((request.readyState == 4) & (request.status == 200)) {
      var response = request.responseText;
      //   alert(response);
      if (response == "Success") {
        document.getElementById("msg1").innerHTML =
          "Registration Successfully Completed";
        document.getElementById("msg1").className =
          "d-flex  justify-content-center alert alert-primary  ";
        document.getElementById("msgDiv1").className = "d-block";

        fname.value = "";
        lname.value = "";
        email.value = "";
        mobile.value = "";
        username.value = "";
        password.value = "";
      } else {
        document.getElementById("msg1").innerHTML = response;
        document.getElementById("msg1").className =
          "d-flex justify-content-center alert alert-danger";
        document.getElementById("msgDiv1").className = "d-block";
      }
    }
  };

  request.open("POST", "signUpProcess.php", true);
  request.send(f);
}

function signin() {
  var un = document.getElementById("un");
  var pw = document.getElementById("pw");
  var rm = document.getElementById("rm");

  var f = new FormData();

  f.append("u", un.value);
  f.append("p", pw.value);
  f.append("r", rm.checked);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if ((request.readyState == 4) & (request.status == 200)) {
      var response = request.responseText;

      // alert(response);
      if (response == "Success") {
        window.location = "index.php";
      } else {
        document.getElementById("msg2").innerHTML = response;
        document.getElementById("msg2").className =
          "d-flex justify-content-center alert alert-danger mt-2";
        document.getElementById("msgDiv2").className = "d-block ";
      }
    }
  };

  request.open("POST", "signInProcess.php", true);
  request.send(f);
}

function adminsign() {
  location.reload();
}
function adminSignIn() {
  var username = document.getElementById("un");
  var password = document.getElementById("pw");

  var f = new FormData();

  f.append("u", username.value);
  f.append("p", password.value);

  request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if ((request.readyState == 4) & (request.status == 200)) {
      var response = request.responseText;
      // alert(response);
      if (response == "Success") {
        window.location = "adminDashboard.php";
      } else {
        document.getElementById("msg").innerHTML = response;
        document.getElementById("msgDiv").className = "d-block  ";
      }
    }
  };

  request.open("POST", "adminSIgninProcess.php", true);
  request.send(f);
}

function loadUser() {
  var request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if ((request.readyState == 4) & (request.status == 200)) {
      var response = request.responseText;

      //  alert(response);

      document.getElementById("tb").innerHTML = response;
    }
  };

  request.open("POST", "loadUserProcess.php", true);
  request.send();
}

function loadstatus() {
  location.reload();
}

function changeStatus() {
  var userid = document.getElementById("userid");
  var tokenElement = document.querySelector('meta[name="admin-csrf-token"]');

  if (!userid || !tokenElement || !tokenElement.content) {
    document.getElementById("msg").textContent =
      "Security token missing. Please reload the admin page.";
    document.getElementById("msgDiv").className = "d-block";
    return;
  }

  var f = new FormData();
  f.append("u", userid.value);
  f.append("csrf_token", tokenElement.content);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      var response = request.responseText.trim();

      if (response === "activate") {
        document.getElementById("msg").textContent = "Successfully Activated";
        document.getElementById("msg").className = "alert alert-success";
        document.getElementById("msgDiv").className = "d-block";
        userid.value = "";
      } else if (response === "deactivate") {
        document.getElementById("msg").textContent = "Successfully Deactivated";
        document.getElementById("msg").className = "alert alert-success";
        document.getElementById("msgDiv").className = "d-block";
        userid.value = "";
      } else {
        document.getElementById("msg").textContent = response;
        document.getElementById("msgDiv").className = "d-block";
      }
    } else if (request.readyState === 4) {
      document.getElementById("msg").textContent =
        "Unable to update user status. Please try again.";
      document.getElementById("msgDiv").className = "d-block";
    }
  };

  request.open("POST", "chageStatusProcess.php", true);
  request.send(f);
}

function brandregload() {
  location.reload();
}

function brandreg() {
  var brand = document.getElementById("brand");

  if (!/^[a-zA-Z0-9]+$/.test(brand.value)) {
    // alert("Please Enter Valid Characters");
    document.getElementById("msg1").innerHTML = "Please Enter Valid Characters";
          document.getElementById("msgDive1").className = "d-block";
  } else {
    var f = new FormData();
    f.append("m", brand.value);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
      if ((request.readyState == 4) & (request.status == 200)) {
        var response = request.responseText;

        if (response == "Success") {
          document.getElementById("msg1").innerHTML = "Brand Registered";
          document.getElementById("msg1").className = "alert alert-success";
          document.getElementById("msgDive1").className = "d-block";
          brand.value = "";
        } else {
          document.getElementById("msg1").innerHTML = response;
          document.getElementById("msgDive1").className = "d-block";
          brand.value = "";
        }
      }
    };

    request.open("POST", "brandRegProcess.php", true);
    request.send(f);
  }
}

function catload() {
  location.reload();
}

function catreg() {
  var cat = document.getElementById("category");

  if (!/^[a-zA-Z0-9]+$/.test(cat.value)) {
    // alert("Please Enter Valid Characters");
    document.getElementById("msg2").innerHTML = "Please Enter Valid Characters";
          document.getElementById("msgDiv2").className = "d-block";
  } else {
    var f = new FormData();
    f.append("c", cat.value);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
      if ((request.readyState == 4) & (request.status == 200)) {
        var response = request.responseText;
        if (response == "Success") {
          document.getElementById("msg2").innerHTML = "Brand Registered";
          document.getElementById("msg2").className = "alert alert-success";
          document.getElementById("msgDiv2").className = "d-block";
          cat.value = "";
        } else {
          document.getElementById("msg2").innerHTML = response;
          document.getElementById("msgDiv2").className = "d-block";
          cat.value = "";
        }
      }
    };

    request.open("POST", "categoryRegistrationProcess.php", true);
    request.send(f);
  }
}

function loadReg() {
  location.reload();
}

function sfReg() {
  var sf = document.getElementById("sf");
  if (!/^[a-zA-Z0-9]+$/.test(sf.value)) {
    // alert("Please Enter Valid Characters");
    document.getElementById("msg3").innerHTML = "Please Enter Valid Characters";
          document.getElementById("msgDiv3").className = "d-block";
  } else {
    var f = new FormData();
    f.append("c", sf.value);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
      if ((request.readyState == 4) & (request.status == 200)) {
        var response = request.responseText;
        if (response == "Success") {
          document.getElementById("msg3").innerHTML = "Scent Family Registered";
          document.getElementById("msg3").className = "alert alert-success";
          document.getElementById("msgDiv3").className = "d-block";
          sf.value = "";
        } else {
          document.getElementById("msg3").innerHTML = response;
          document.getElementById("msgDiv3").className = "d-block";
          sf.value = "";
        }
      }
    };

    request.open("POST", "sfRegProcess.php", true);
    request.send(f);
  }
}

function loadsize() {
  location.reload();
}

function sizeReg() {
  var size = document.getElementById("size");

  var f = new FormData();
  f.append("s", size.value);

  if (!/^[a-zA-Z0-9]+$/.test(size.value)) {
    // alert("Please Enter Valid Characters");
    document.getElementById("msg4").innerHTML = "Please Enter Valid Characters";
          document.getElementById("msgDiv4").className = "d-block";
  } else {
    var request = new XMLHttpRequest();

    request.onreadystatechange = function () {
      if ((request.readyState == 4) & (request.status == 200)) {
        var response = request.responseText;
        if (response == "Success") {
          document.getElementById("msg4").innerHTML = "Size Registered";
          document.getElementById("msg4").className = "alert alert-success";
          document.getElementById("msgDiv4").className = "d-block";
          size.value = "";
        } else {
          document.getElementById("msg4").innerHTML = response;
          document.getElementById("msgDiv4").className = "d-block";
          size.value = "";
        }
      }
    };

    request.open("POST", "sizeRegProcess.php", true);
    request.send(f);
  }
}

function loadconcentration() {
  location.reload();
}

function concentrationReg() {
  var con = document.getElementById("con");

  var f = new FormData();
  f.append("c", con.value);

  if (!/^[a-zA-Z0-9]+$/.test(con.value)) {
    // alert("Please Enter Valid Characters");
    document.getElementById("msg5").innerHTML = "Please Enter Valid Characters";
          document.getElementById("msgDiv5").className = "d-block";
  } else {
    var request = new XMLHttpRequest();

    request.onreadystatechange = function () {
      if ((request.readyState == 4) & (request.status == 200)) {
        var response = request.responseText;
        if (response == "Success") {
          document.getElementById("msg5").innerHTML = "concentration Registered";
          document.getElementById("msg5").className = "alert alert-success";
          document.getElementById("msgDiv5").className = "d-block";
          con.value = "";
        } else {
          document.getElementById("msg5").innerHTML = response;
          document.getElementById("msgDiv5").className = "d-block";
          con.value = "";
        }
      }
    };

    request.open("POST", "concentrationRegProcess.php", true);
    request.send(f);
  }
}

function regProduct() {
  var pname = document.getElementById("pn");
  var brand = document.getElementById("brand");
  var cat = document.getElementById("cat");
  var sf = document.getElementById("sf");
  var size = document.getElementById("size");
  var con = document.getElementById("con");
  var desc = document.getElementById("desc");
  var file = document.getElementById("file");
  var tokenElement = document.querySelector('meta[name="admin-csrf-token"]');

  if (!pname || !brand || !cat || !sf || !size || !con || !desc || !file) {
    Swal.fire({ icon: "error", title: "Product registration form is incomplete." });
    return;
  }

  var productName = pname.value.trim();
  var description = desc.value.trim();
  var validProductName = /^[\p{L}\p{N}][\p{L}\p{N}\s&'’().,+\-/ #]*$/u;

  if (!productName || !validProductName.test(productName) || Array.from(productName).length > 30) {
    Swal.fire({
      icon: "error",
      title: "Enter a valid product name (30 characters or fewer).",
      text: "Spaces and common punctuation are allowed."
    });
    pname.focus();
    return;
  }

  if (!brand.value || brand.value === "0" || !cat.value || cat.value === "0" ||
      !sf.value || sf.value === "0" || !size.value || size.value === "0" ||
      !con.value || con.value === "0") {
    Swal.fire({ icon: "error", title: "Please select all product details." });
    return;
  }

  if (!description || Array.from(description).length > 200) {
    Swal.fire({ icon: "error", title: "Enter a description of 200 characters or fewer." });
    desc.focus();
    return;
  }

  if (!file.files || file.files.length !== 1) {
    Swal.fire({ icon: "error", title: "Please select a product image." });
    file.focus();
    return;
  }

  var selectedFile = file.files[0];
  var allowedTypes = ["image/jpeg", "image/png", "image/webp"];
  if (!allowedTypes.includes(selectedFile.type) || selectedFile.size > 5 * 1024 * 1024) {
    Swal.fire({
      icon: "error",
      title: "Choose a JPG, PNG or WEBP image up to 5 MB."
    });
    file.focus();
    return;
  }

  if (!tokenElement || !tokenElement.content) {
    Swal.fire({ icon: "error", title: "Security token missing. Reload the admin stock page." });
    return;
  }

  var formData = new FormData();
  formData.append("pname", productName);
  formData.append("brand", brand.value);
  formData.append("cat", cat.value);
  formData.append("sf", sf.value);
  formData.append("size", size.value);
  formData.append("con", con.value);
  formData.append("desc", description);
  formData.append("image", selectedFile);
  formData.append("csrf_token", tokenElement.content);

  var request = new XMLHttpRequest();
  request.open("POST", "PoductRegister.php", true);
  request.setRequestHeader("X-Requested-With", "XMLHttpRequest");

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    var response = (request.responseText || "").trim();
    if (request.status === 200 && response === "Success") {
      pname.value = "";
      brand.value = "0";
      cat.value = "0";
      sf.value = "0";
      size.value = "0";
      con.value = "0";
      desc.value = "";
      file.value = "";

      Swal.fire({
        position: "top-center",
        icon: "success",
        title: "Product registered successfully",
        showConfirmButton: false,
        timer: 2000
      }).then(function () {
        location.reload();
      });
      return;
    }

    if (request.status === 403) {
      Swal.fire({ icon: "error", title: response || "Your session or security token has expired. Reload the page." });
      return;
    }

    Swal.fire({
      icon: "error",
      title: response || "Product registration failed. Please try again."
    });
  };

  request.onerror = function () {
    Swal.fire({ icon: "error", title: "Network error. Please try again." });
  };

  request.send(formData);
}

function updateStock() {
  var pname = document.getElementById("pname");
  var qty = document.getElementById("qty");
  var up = document.getElementById("up");
  var tokenElement = document.querySelector('meta[name="admin-csrf-token"]');

  if (!pname || !qty || !up || !tokenElement || !tokenElement.content) {
    Swal.fire({
      icon: "error",
      title: "Security token missing. Please reload the admin stock page."
    });
    return;
  }

  if (!/^[1-9]\d{0,9}$/.test(qty.value.trim())) {
    Swal.fire({
      icon: "error",
      title: "Please enter a valid positive whole-number quantity."
    });
    return;
  }

  var price = up.value.trim();

  if (
    price.length > 10 ||
    !/^[0-9]{1,8}(?:\.[0-9]{1,2})?$/.test(price) ||
    !Number.isFinite(Number(price)) ||
    Number(price) <= 0
  ) {
    Swal.fire({
      icon: "error",
      title: "Please enter a valid positive unit price."
    });
    return;
  }

  if (!/^[1-9]\d*$/.test(pname.value)) {
    Swal.fire({
      icon: "error",
      title: "Please select a product."
    });
    return;
  }

  var f = new FormData();
  f.append("pname", pname.value);
  f.append("qty", qty.value.trim());
  f.append("up", price);
  f.append("csrf_token", tokenElement.content);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    var response = request.responseText.trim();

    if (
      request.status === 200 &&
      (response === "New Stock Added Successfull" ||
       response === "Stock Updated Successfull")
    ) {
      Swal.fire({
        position: "top-center",
        icon: "success",
        title: response,
        showConfirmButton: false,
        timer: 2000
      });

      pname.value = "";
      qty.value = "";
      up.value = "";

      if (typeof loadReg === "function") {
        loadReg();
      }

    } else {
      if (typeof loadReg === "function") {
        loadReg();
      }

      Swal.fire({
        icon: "error",
        title: response || "Unable to update stock. Please try again."
      });
    }
  };

  request.onerror = function () {
    Swal.fire({
      icon: "error",
      title: "Network error. Please try again."
    });
  };

  request.open("POST", "updateStockProccess.php", true);
  request.send(f);
}
function printDiv() {
  var originalContent = document.body.innerHTML;

  var printArea = document.getElementById("printArea").innerHTML;

  document.body.innerHTML = printArea;

  window.print();

  document.body.innerHTML = originalContent;
}

function loadProduct(x) {
  var page = x;
  // alert(page);

  var f = new FormData();
  f.append("p", page);

  var request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);
      document.getElementById("pid").innerHTML = response;
    }
  };

  request.open("POST", "loadProductProcess.php", true);
  request.send(f);
}

function searchProduct(x) {
  // alert("ok");

  var page = x;
  var product = document.getElementById("product");
  // alert(page);
  // alert(product.value);

  var f = new FormData();
  f.append("p", product.value);
  f.append("pg", page);

  var request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);

      document.getElementById("pid").innerHTML = response;
    }
  };

  request.open("POST", "searchProductProcess.php", true);
  request.send(f);
}

function adfilter() {
  // alert("ok");
  var advsch = document.getElementById("advsch");
  var hdiv = document.getElementById("hdiv");

  advsch.classList.toggle("d-none");
  hdiv.classList.toggle("d-none");
}

function advSearchProduct(x) {
  var page = x;
  // alert(page);

  var sf = document.getElementById("sf");
  var brand = document.getElementById("brand");
  var cat = document.getElementById("cat");
  var size = document.getElementById("size");
  var con = document.getElementById("con");
  var min = document.getElementById("min");
  var max = document.getElementById("max");

  // alert(min.value);

  var f = new FormData();
  f.append("pg", page);
  f.append("sf", sf.value);
  f.append("brand", brand.value);
  f.append("cat", cat.value);
  f.append("s", size.value);
  f.append("con", con.value);
  f.append("min", min.value);
  f.append("max", max.value);

  var request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);
      document.getElementById("pid").innerHTML = response;
    }
  };

  request.open("POST", "advSearchProductProccess.php", true);
  request.send(f);
}

function uploadImage() {
  var img = document.getElementById("imgUploader");

  if (!img || !img.files || !img.files[0]) {
    Swal.fire({
      icon: "question",
      title: "Please Select Your Image"
    });
    return;
  }

  var tokenElement = document.getElementById("csrfToken");

  if (!tokenElement || !tokenElement.value) {
    Swal.fire("Error", "Refresh the page and try again.", "error");
    return;
  }

  var f = new FormData();
  f.append("i", img.files[0]);
  f.append("csrf_token", tokenElement.value);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    if (request.status === 200) {
      var response = request.responseText.trim();

      if (
        response &&
        response.indexOf("resources/profileImg/") === 0 &&
        !response.includes("..")
      ) {
        document.getElementById("i").src =
          response + "?v=" + Date.now();

        Swal.fire({
          icon: "success",
          title: "Profile Update Successfull"
        });

        img.value = "";
      } else {
        Swal.fire("Error", "Unexpected upload response.", "error");
      }
    } else {
      Swal.fire(
        "Upload Failed",
        request.responseText || "Please try again.",
        "error"
      );
    }
  };

  request.open("POST", "ProfileImgUploadProcess.php", true);
  request.send(f);
}
function updateData() {
  var fields = ["fname", "lname", "email", "mobile", "no", "line1", "line2"];
  var f = new FormData();

  fields.forEach(function (id) {
    f.append(
      {
        fname: "f",
        lname: "l",
        email: "e",
        mobile: "m",
        no: "n",
        line1: "l1",
        line2: "l2"
      }[id],
      document.getElementById(id).value
    );
  });

  f.append("csrf_token", document.getElementById("csrfToken").value);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState === 4) {
      var response = request.responseText.trim();

      if (request.status === 200 && response === "Update Successfully Completed") {
        Swal.fire({
          position: "top-center",
          icon: "success",
          title: response,
          showConfirmButton: false,
          timer: 2500
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Profile Update",
          text: response || "Profile update failed. Please try again."
        });
      }
    }
  };

  request.open("POST", "updateDataProccess.php", true);
  request.send(f);
}

function changePassword() {
  var currentPassword = document.getElementById("currentPassword");
  var newPassword = document.getElementById("newPassword");
  var confirmPassword = document.getElementById("confirmPassword");

  var f = new FormData();

  f.append("current_password", currentPassword.value);
  f.append("new_password", newPassword.value);
  f.append("confirm_password", confirmPassword.value);
  f.append("csrf_token", document.getElementById("csrfToken").value);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState === 4) {
      var response = request.responseText.trim();

      if (request.status === 200 && response === "Password Changed Successfully") {
        currentPassword.value = "";
        newPassword.value = "";
        confirmPassword.value = "";

        Swal.fire({
          icon: "success",
          title: response,
          showConfirmButton: false,
          timer: 2500
        });
      } else {
        Swal.fire({
          icon: "error",
          title: "Password Change",
          text: response || "Password change failed. Please try again."
        });
      }
    }
  };

  request.open("POST", "changePasswordProcess.php", true);
  request.send(f);
}

function signOut() {
  // alert("ok");
  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);
      
      if (response == "Sign Out Successfull") {
        Swal.fire({
          position: "top-center",
          icon: "success",
          title: response,
          showConfirmButton: false,
          timer: 2500
        });
        
      }else{
        
        Swal.fire({
          position: "top-center",
          icon: "error",
          title: response,
          showConfirmButton: false,
          timer: 2000
        });
      }
      location.reload();
    }
  };

  request.open("POST", "signOutProccess.php", true);
  request.send();
}

function getCartCsrfToken() {
  var tokenElement = document.querySelector('meta[name="cart-csrf-token"]');
  return tokenElement ? tokenElement.getAttribute("content") : "";
}

function addToCart(x) {
  var stockId = x;
  var qty = document.getElementById("qty");
  var quantity = qty ? Number(qty.value) : 0;
  var csrfToken = getCartCsrfToken();

  if (!csrfToken) {
    Swal.fire("Security error", "Please refresh the page and try again.", "error");
    return;
  }

  if (!Number.isSafeInteger(quantity) || quantity < 1) {
    Swal.fire({
      icon: "error",
      title: "Please Enter Valid Quantity"
    });
    return;
  }

  var f = new FormData();
  f.append("s", stockId);
  f.append("q", quantity);
  f.append("csrf_token", csrfToken);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    var response = request.responseText.trim();

    if (
      request.status === 200 &&
      (response === "Cart Item Updated Successfull" ||
       response === "Cart Item Added Successfull")
    ) {
      Swal.fire({
        position: "top-center",
        icon: "success",
        title: response,
        showConfirmButton: false,
        timer: 2000
      });

      if (qty) qty.value = "1";
    } else {
      Swal.fire({
        icon: "error",
        title: response || "Unable to add this item to your cart."
      });
    }
  };

  request.open("POST", "addToCartProccess.php", true);
  request.send(f);
}

function loadCart() {
  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState === 4 && request.status === 200) {
      document.getElementById("cartBody").innerHTML = request.responseText;
    }
  };

  request.open("POST", "loadCartProccess.php", true);
  request.send();
}

function incrementCartQty(x) {
  var cartId = x;
  var qty = document.getElementById("qty" + x);

  if (!qty) return;

  var currentQty = Number(qty.value);
  var newQty = currentQty + 1;
  var csrfToken = getCartCsrfToken();

  if (!Number.isSafeInteger(newQty) || newQty < 1 || !csrfToken) {
    alert("Unable to update cart. Please refresh the page.");
    return;
  }

  var f = new FormData();
  f.append("c", cartId);
  f.append("q", newQty);
  f.append("csrf_token", csrfToken);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    var response = request.responseText.trim();

    if (request.status === 200 && response === "Success") {
      qty.value = String(newQty);
      loadCart();
    } else {
      alert(response || "Unable to update cart quantity.");
    }
  };

  request.open("POST", "updateCartQtyProccess.php", true);
  request.send(f);
}

function decrementCartQty(x) {
  var cartId = x;
  var qty = document.getElementById("qty" + x);

  if (!qty) return;

  var currentQty = Number(qty.value);
  var newQty = currentQty - 1;

  if (!Number.isSafeInteger(newQty) || newQty < 1) return;

  var csrfToken = getCartCsrfToken();

  if (!csrfToken) {
    alert("Unable to update cart. Please refresh the page.");
    return;
  }

  var f = new FormData();
  f.append("c", cartId);
  f.append("q", newQty);
  f.append("csrf_token", csrfToken);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    var response = request.responseText.trim();

    if (request.status === 200 && response === "Success") {
      qty.value = String(newQty);
      loadCart();
    } else {
      alert(response || "Unable to update cart quantity.");
    }
  };

  request.open("POST", "updateCartQtyProccess.php", true);
  request.send(f);
}

function removeCart(x) {
  if (!confirm("Are you sure deleting this item?")) return;

  var csrfToken = getCartCsrfToken();

  if (!csrfToken) {
    alert("Unable to remove item. Please refresh the page.");
    return;
  }

  var f = new FormData();
  f.append("c", x);
  f.append("csrf_token", csrfToken);

  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState !== 4) return;

    var response = request.responseText.trim();

    if (request.status === 200) {
      alert(response);
      loadstatus();
    } else {
      alert(response || "Unable to remove this cart item.");
    }
  };

  request.open("POST", "removeCartProcess.php", true);
  request.send(f);
}
function checkout() {
  prepareZeyaraCheckout(true, null, null);
}

function prepareZeyaraCheckout(isCart, stockId, quantity) {
  if (typeof payhere === "undefined") {
    Swal.fire("Payment unavailable", "The PayHere payment service could not be loaded.", "error");
    return;
  }

  var tokenRequest = new XMLHttpRequest();

  tokenRequest.onreadystatechange = function () {
    if (tokenRequest.readyState !== 4) return;

    if (tokenRequest.status !== 200) {
      Swal.fire("Session error", "Please refresh the page and sign in again.", "error");
      return;
    }

    var tokenData;

    try {
      tokenData = JSON.parse(tokenRequest.responseText);
    } catch (error) {
      Swal.fire("Error", "Unable to prepare secure checkout.", "error");
      return;
    }

    if (!tokenData.success || !tokenData.csrf_token) {
      Swal.fire("Error", tokenData.message || "Unable to prepare checkout.", "error");
      return;
    }

    var form = new FormData();
    form.append("cart", isCart ? "true" : "false");
    form.append("csrf_token", tokenData.csrf_token);

    if (!isCart) {
      form.append("stockId", stockId);
      form.append("qty", quantity);
    }

    var paymentRequest = new XMLHttpRequest();

    paymentRequest.onreadystatechange = function () {
      if (paymentRequest.readyState !== 4) return;

      var result;

      try {
        result = JSON.parse(paymentRequest.responseText);
      } catch (error) {
        Swal.fire("Checkout error", "The server returned an invalid response.", "error");
        return;
      }

      if (
        paymentRequest.status !== 200 ||
        !result.success ||
        !result.payment ||
        !result.order_id ||
        !result.order_history_id
      ) {
        Swal.fire("Checkout error", result.message || "Unable to prepare your order.", "error");
        return;
      }

      doCheckout(
        result.payment,
        result.order_id,
        result.order_history_id,
        tokenData.csrf_token
      );
    };

    paymentRequest.open("POST", "paymentProcess.php", true);
    paymentRequest.send(form);
  };

  tokenRequest.open("POST", "paymentToken.php", true);
  tokenRequest.send();
}

function doCheckout(payment, orderReference, orderHistoryId, csrfToken) {
  var paymentStarted = false;

  function checkVerifiedPayment(attempt) {
    var form = new FormData();
    form.append("order_id", orderReference);
    form.append("csrf_token", csrfToken);

    var request = new XMLHttpRequest();

    request.onreadystatechange = function () {
      if (request.readyState !== 4) return;

      var result;

      try {
        result = JSON.parse(request.responseText);
      } catch (error) {
        if (attempt < 30) {
          setTimeout(function () {
            checkVerifiedPayment(attempt + 1);
          }, 2000);
        } else {
          Swal.fire(
            "Payment processing",
            "We could not confirm the payment yet. Please check your order history before trying again.",
            "info"
          );
        }
        return;
      }

      if (request.status === 200 && result.success) {
        if (result.payment_status === "PAID") {
          window.location.href =
            "invoice.php?orderId=" + encodeURIComponent(orderHistoryId);
          return;
        }

        if (result.payment_status === "FAILED") {
          Swal.fire("Payment failed", "Your payment was not completed.", "error");
          return;
        }

        if (result.payment_status === "CANCELLED") {
          Swal.fire("Payment cancelled", "Your payment was cancelled.", "info");
          return;
        }

        if (result.payment_status === "REVIEW") {
          Swal.fire(
            "Payment under review",
            "Your payment needs manual verification. Please contact the store before making another payment.",
            "warning"
          );
          return;
        }

        if (result.payment_status === "CHARGEDBACK") {
          Swal.fire(
            "Payment issue",
            "This payment has been marked as charged back. Please contact the store.",
            "warning"
          );
          return;
        }

        if (result.payment_status === "PENDING" && attempt < 30) {
          setTimeout(function () {
            checkVerifiedPayment(attempt + 1);
          }, 2000);
          return;
        }
      }

      if (attempt < 30) {
        setTimeout(function () {
          checkVerifiedPayment(attempt + 1);
        }, 2000);
      } else {
        Swal.fire(
          "Payment processing",
          "Payment confirmation is taking longer than expected. Check your order history before attempting another payment.",
          "info"
        );
      }
    };

    request.open("POST", "paymentStatus.php", true);
    request.send(form);
  }

  payhere.onCompleted = function () {
    // Browser callbacks are not proof of payment.
    // Only the server-verified order status can authorize an invoice.
    checkVerifiedPayment(0);
  };

  payhere.onDismissed = function () {
    Swal.fire(
      "Payment window closed",
      "The payment has not been confirmed. If you completed payment, check your order history before trying again.",
      "info"
    );
  };

  payhere.onError = function (error) {
    console.error("PayHere error:", error);

    Swal.fire(
      "Payment error",
      "The payment service reported an error. Check your order history before retrying.",
      "error"
    );
  };

  try {
    paymentStarted = true;
    payhere.startPayment(payment);
  } catch (error) {
    console.error("Unable to start PayHere:", error);

    Swal.fire(
      "Payment unavailable",
      "Unable to open the payment window. Check your order history before retrying.",
      "error"
    );
  }
}

function buyNow(stockId) {
  var qtyInput = document.getElementById("qty");

  if (!qtyInput) {
    Swal.fire("Error", "Quantity field was not found.", "error");
    return;
  }

  var quantity = Number(qtyInput.value);
  var parsedStockId = Number(stockId);

  if (
    !Number.isInteger(parsedStockId) ||
    parsedStockId < 1 ||
    !Number.isInteger(quantity) ||
    quantity < 1
  ) {
    Swal.fire({
      icon: "error",
      title: "Please enter a valid quantity"
    });
    return;
  }

  prepareZeyaraCheckout(false, parsedStockId, quantity);
}
function forgetPassword() {
  // alert("ok");
  var email = document.getElementById("e");

  if (email.value != "") {
    var f = new FormData();
    f.append("e", email.value);

    var request = new XMLHttpRequest();
    request.onreadystatechange = function () {
      if (request.readyState == 4 && request.status == 200) {
        var response = request.responseText;
        // alert(response);

        if (response == "Success") {
          document.getElementById("msg").innerHTML =
            "Email Sent! Please Check your email box";
          document.getElementById("msg").className = "alert alert-success";
          document.getElementById("msgDiv").className = "d-block";
          email.value = "";
        } else {
          document.getElementById("msg").innerHTML = response;
          document.getElementById("msg").className = "alert alert-danger";
          document.getElementById("msgDiv").className = "d-block";
        }
      }
    };

    request.open("POST", "forgetPasswordProccess.php", true);
    request.send(f);
  } else {
    alert("Please Enter your valid email");
  }
}

function resetPassword() {
  // alert("ok");
  var vcode = document.getElementById("vcode");
  var np = document.getElementById("np");
  var np2 = document.getElementById("np2");

  var f = new FormData();
  f.append("vcode", vcode.value);
  f.append("np", np.value);
  f.append("np2", np2.value);

  var request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);
      if (response == "Success") {
        window.location = "Signin.php";
      } else {
        document.getElementById("msg").innerHTML = response;
        document.getElementById("msgDiv2").className = " d-block";
      }
    }
  };

  request.open("POST", "restPasswordProccess.php", true);
  request.send(f);
}

function loadchart() {
  // alert("ok");
  var ctx = document.getElementById("myChart");

  var request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);
      var data = JSON.parse(response);
      new Chart(ctx, {
        type: "doughnut",
        data: {
          labels: data.lables,
          datasets: [
            {
              label: "# of Votes",
              data: data.data,
              borderWidth: 1,
            },
          ],
        },
        options: {
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });
    }
  };

  request.open("POST", "loadChartProcees.php", true);
  request.send();
}

function printinvc() {
  var originalContent = document.body.innerHTML;
  var printareainvc = document.getElementById("printinvoice").innerHTML;

  document.body.innerHTML = printareainvc;

  window.print();

  document.body.innerHTML = originalContent;
}

function printpurchhis() {
  var originalContent = document.body.innerHTML;
  var printareainvc = document.getElementById("ordhid").innerHTML;

  document.body.innerHTML = printareainvc;

  window.print();

  document.body.innerHTML = originalContent;
}

function printqoute() {
  var originalContent = document.body.innerHTML;
  var printareainvc = document.getElementById("cartBody").innerHTML;

  document.body.innerHTML = printareainvc;

  window.print();

  document.body.innerHTML = originalContent;
}

function signOutadmin() {
  // alert("ok");
  var request = new XMLHttpRequest();

  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);

      if (response == "Sign Out Succefull") {
       
        window.location = "adminSignIn.php";
      } 
     
    }
  };

  request.open("POST", "signOutAdminProccess.php", true);
  request.send();
}

function checkAdmin() {
  var email = document.getElementById("e");

  var f = new FormData();
  f.append("email", email.value);

  var request = new XMLHttpRequest();
  request.onreadystatechange = function () {
    if (request.readyState == 4 && request.status == 200) {
      var response = request.responseText;
      // alert(response);
      if (response == "Success") {
        forgetPassword();
      } else {
        alert(response);
      }
    }
  };
  request.open("POST", "checkAdminProccess.php", true);
  request.send(f);
}

function loadsales(){
  // alert("ok");

  var request = new XMLHttpRequest();
  request.onreadystatechange = function(){
    if (request.readyState == 4 && request.status == 200) {
        var response = request.responseText;
          // alert(response);
          document.getElementById("tb11").innerHTML = response;
    }
  };

  request.open("POST","loadSaleProcess.php",true);
  request.send();

}




