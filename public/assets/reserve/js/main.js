// show steps

const allSteps = document.querySelectorAll("form .step");
const thankStep = document.querySelector(".thanks-step");
const nextBtn = document.getElementById("next");
const backBtn = document.getElementById("back");
// const changeCarBtn = document.querySelector("#changeCar");
const addonss = document.querySelectorAll(".addons .addon");
const addonChecks = document.querySelectorAll(".addons .form-check-input");
const carName = document.querySelector(".car-name");
const carPriceSp = document.querySelector(".car-price");
const monthCar = document.querySelector("#month-car");
const yearCar = document.querySelector("#year-car");
const monthlyAddons = document.querySelector("#monthly-addons");
const yearlyAddons = document.querySelector("#yearly-addons");
const checkoutForm = document.getElementById("checkoutForm");
const mcarCards = document.querySelectorAll("#month-car .car");
const ycarCards = document.querySelectorAll("#year-car .car");
const monthlyCarBtns = document.querySelectorAll("#month-car .car-type");
const yearlyCarBtns = document.querySelectorAll("#year-car .car-type");
const pricingTables = document.getElementsByClassName("table");
const themeMode = document.querySelector(".theme-mode");





//vars
let currentStep = 0;

// changeCarBtn.addEventListener("click", function () {
//   currentStep = 1;
//   updateStep();
// });

//Run
updateStep();
chooseCar();
// chooseAddons();

//form steps
function updateStep() {
  // مخفی کردن تمام مراحل
  allSteps.forEach((step) => {
    step.classList.add("d-none");
  });

  // نمایش مرحله فعلی
  allSteps[currentStep].classList.remove("d-none");

  // مدیریت دکمه برگشت
  if (currentStep == 0) {
    backBtn.classList.add("d-none");
  } else {
    backBtn.classList.remove("d-none");
  }

  // تغییر متن و رفتار دکمه Next در مراحل مختلف
  if (currentStep == allSteps.length - 1) {
    nextBtn.innerHTML = "Confirm and Submit";
    nextBtn.classList.add("done");
  } else {
    nextBtn.innerHTML = "Next";
    nextBtn.classList.remove("done");
  }
}


nextBtn.addEventListener("click", nextStep);
backBtn.addEventListener("click", backStep);

function nextStep(e) {
  if (currentStep == 0 || currentStep == 1 || currentStep == 2|| currentStep == 3) {
    if (!formChecker()) {
      return 0;
    }
  }

  if (nextBtn.classList.contains("done")) {
    // تغییر نوع دکمه به submit
    nextBtn.type = "submit";

    // ارسال فرم و نمایش پیام تشکر
    checkoutForm.submit();
    checkoutForm.classList.add("d-none");
    thankStep.classList.remove("d-none");
  } else {
    currentStep++; // حرکت به مرحله بعد
    updateStep();
  }
}

function backStep() {
  if (currentStep == 3) {
    currentStep--;
    updateStep();
  } else {
    currentStep--;
    updateStep();
  }
}

// remove any addon if car changed
function removeIfChange() {
  if (addonDiv) {
    addonDiv.forEach((addon) => {
      removeAddon(addon.id);
    });
  }
}

//choose car
function chooseCar(btns = monthlyCarBtns, cars = mcarCards) {
  // انتخاب تمام دکمه‌های رادیو و والدین آن‌ها

  // افزودن رویداد به هر دکمه
  btns.forEach((btn, ind) => {
    btn.addEventListener("change", function () {
      // حذف کلاس checked از تمام آیتم‌ها
      cars.forEach((car) => {
        car.classList.remove("checked");
      });

      // افزودن کلاس checked به آیتم انتخاب‌شده
      if (btn.checked) {
        cars[ind].classList.add("checked");
      }
    });
  });

}

//add car to summary
function addCar(btnValue) {
  let [dur, type] = btnValue.split("-");
  let period = dur == "month" ? "Month" : "Year";
  let carPrice = carPrices[type][dur];
  carName.innerHTML = `${type} (${period}ly)`;
  carPriceSp.innerHTML = `+$${carPrice}/${period}`;
  addonCarPrices.set("carType", period);
  addonCarPrices.set("carPrice", carPrice);
}

// Disabling form submissions if there are invalid fields
function formChecker() {

  let valid = true;
  if (currentStep == 0) {
    let email = checkoutForm.email;
    let firstName = checkoutForm.first_name;
    let lastName = checkoutForm.last_name;
    let phone = checkoutForm.phone;
    let messengerPhone = checkoutForm.messenger_phone;
    let returnDate = checkoutForm.return_date;
    let pickupDate = checkoutForm.pickup_date;
    let pickupLocation = checkoutForm.pickup_location;
    let returnLocation = checkoutForm.return_location;

    // Validate Return Date is after Pickup Date
    if (!isReturnDateAfterPickupDate(pickupDate, returnDate)) {


      valid = false;
    }
    // Validate Pickup Date
    if (!isValidDate(pickupDate)) {

      valid = false;
    }

    if (!isValidDate(returnDate)) {

      valid = false;
    }

    // Validate First Name
    if (!isValidName(firstName)) {

      valid = false;
    }

    // Validate Last Name
    if (!isValidName(lastName)) {

      valid = false;
    }

    // Validate Email
    if (!isValidEmail(email)) {

      valid = false;
    }

    // Validate Phone
    if (!isValidPhone(phone)) {

      valid = false;
    }
    // Validate Pickup Location
    if (!isValidPickupLocation(pickupLocation)) {

      valid = false;
    }

    // Validate Return Location
    if (!isValidReturnLocation(returnLocation)) {

      valid = false;
    }

    if (!isValidMessengerPhone(messengerPhone)) {

      valid = false;
    }

    return valid;
  }

  if (currentStep == 1) {

    if (!validateCars()) {
      valid = false;
    }
    return valid;
  }

  if (currentStep == 2) {
    return valid;
  }

  if (currentStep == 3) {
    // اعتبارسنجی چک‌باکس‌ها در مرحله آخر
    let confirmDeposit = checkoutForm.confirm_deposit;
    let acceptTerms = checkoutForm.accept_terms;
    if (!confirmDeposit.checked) {
      confirmDeposit.classList.add("is-invalid");
      valid = false;
    } else {
      confirmDeposit.classList.remove("is-invalid");
      confirmDeposit.classList.add("is-valid");
    }

    if (!acceptTerms.checked) {
      acceptTerms.classList.add("is-invalid");
      valid = false;
    } else {
      acceptTerms.classList.remove("is-invalid");
      acceptTerms.classList.add("is-valid");
    }
    return valid;
  }

}

function isValidName(nameField) {
  let validName = true;
  let re = /\w{3,}/ig;
  if (!re.test(nameField.value)) {
    validName = false;
    nameField.classList.remove("is-valid");
    nameField.classList.add("is-invalid");
  } else {
    nameField.classList.remove("is-invalid");
    nameField.classList.add("is-valid");
  }
  return validName;
}

function isValidEmail(email) {
  let validEmail = true;
  let re = /[a-zA-Z0-9]+@[a-zA-Z0-9]+.\w{1,}/ig;
  if (!re.test(email.value)) {
    validEmail = false;
    email.classList.remove("is-valid");
    email.classList.add("is-invalid");
  }
  else {
    email.classList.remove("is-invalid");
    email.classList.add("is-valid");
  }
  return validEmail
}

function isValidPhone(phoneField) {
  let validPhone = true;
  let re = /^09\d{9}$/; // Regex baraye shomare haye Iran
  let feedbackElement = phoneField.nextElementSibling; // Element ba class 'invalid-feedback'

  if (!re.test(phoneField.value)) {
    validPhone = false;
    phoneField.classList.remove("is-valid");
    phoneField.classList.add("is-invalid");

    // Update message bar asas khata
    if (phoneField.value.length < 11) {
      feedbackElement.textContent = "Phone number must be 11 digits!";
    } else if (!phoneField.value.startsWith("09")) {
      feedbackElement.textContent = "Phone number must start with '09'.";
    } else {
      feedbackElement.textContent = "Invalid phone number format.";
    }
  } else {
    phoneField.classList.remove("is-invalid");
    phoneField.classList.add("is-valid");
    feedbackElement.textContent = ""; // Clear error message
  }

  return validPhone;
}
function isValidMessengerPhone(phoneField) {
  let validMessengerPhone = true;
  let re = /^09\d{9}$/; // Regex baraye shomare haye Iran
  let feedbackElement = phoneField.nextElementSibling; // Element ba class 'invalid-feedback'

  if (!re.test(phoneField.value)) {
    validMessengerPhone = false;
    phoneField.classList.remove("is-valid");
    phoneField.classList.add("is-invalid");

    // Update message bar asas khata
    if (phoneField.value.length < 11) {
      feedbackElement.textContent = "Messenger number must be 11 digits!";
    } else if (!phoneField.value.startsWith("09")) {
      feedbackElement.textContent = "Messenger number must start with '09'.";
    } else {
      feedbackElement.textContent = "Invalid messenger number format.";
    }
  } else {
    phoneField.classList.remove("is-invalid");
    phoneField.classList.add("is-valid");
    feedbackElement.textContent = ""; // Clear error message
  }

  return validMessengerPhone;
}
function validateCars(btns = monthlyCarBtns) {
  let validCar = true;
  let feedback = document.querySelector(".bad-feedback-car");
  let checkedCount = 0;
  btns.forEach((inp) => {
    if (inp.checked) {
      checkedCount++;
    }
  })
  if (checkedCount == 0) {
    feedback.classList.remove("d-none")
    validCar = false
  }
  else {
    feedback.classList.add("d-none")
  }
  return validCar;
}

function isValidDate(dateField) {

  if (!dateField || !dateField.value || isNaN(new Date(dateField.value).getTime())) {
    dateField.classList.remove("is-valid");
    dateField.classList.add("is-invalid");
    return false;
  }
  dateField.classList.remove("is-invalid");
  dateField.classList.add("is-valid");
  return true;
}

function isReturnDateAfterPickupDate(pickupField, returnField) {
  let pickupDate = new Date(pickupField.value);
  let returnDate = new Date(returnField.value);
  let errorDiv = document.getElementById("date-error");

  // Moghayese be timestamp baraye asalat
  if (pickupDate.getTime() > returnDate.getTime()) {
    returnField.classList.remove("is-valid");
    returnField.classList.add("is-invalid");
    errorDiv.classList.remove("d-none"); // Show error message
    return false;
  } else {
    returnField.classList.remove("is-invalid");
    returnField.classList.add("is-valid");
    errorDiv.classList.add("d-none"); // Hide error message
    return true;
  }
}

function isValidPickupLocation(locationField) {
  let validLocation = true;
  if (locationField.value === "") {
    validLocation = false;
    locationField.classList.remove("is-valid");
    locationField.classList.add("is-invalid");
  } else {
    locationField.classList.remove("is-invalid");
    locationField.classList.add("is-valid");
  }
  return validLocation;
}

function isValidReturnLocation(locationField) {
  let validLocation = true;
  if (locationField.value === "") {
    validLocation = false;
    locationField.classList.remove("is-valid");
    locationField.classList.add("is-invalid");
  } else {
    locationField.classList.remove("is-invalid");
    locationField.classList.add("is-valid");
  }
  return validLocation;
}



// choose add-on services
// function chooseAddons() {

//   addonChecks.forEach((check) => {
//     check.addEventListener("click", function () {
//       if (check.checked) {
//         addons(check);
//         check.parentElement.parentElement.classList.add("checkAddon");
//       } else {
//         check.parentElement.parentElement.classList.remove("checkAddon");
//         removeAddon(check.value);
//       }
//     });
//   });
//   //   addonss.forEach((addon) => {
//   //   addon.addEventListener("click", function () {
//   //     const addonChecked = addon.querySelector(".form-check-input");
//   //     if (addonChecked.checked) {
//   //       addonChecked.checked = false;
//   //       this.classList.remove("checkAddon");
//   //       removeAddon(addonChecked.value);
//   //     } else {
//   //       addonChecked.checked = true;
//   //       this.classList.add("checkAddon");
//   //       addons(addonChecked);
//   //     }
//   //   });
//   // });
// }

// // add Addons to summary
// function addons(check) {
//   let [dur, type] = check.value.split("-");
//   let addonName = type.toLowerCase();
//   let addonDur = dur == "month" ? "Month" : "Year";
//   let addonPrice = addonData[addonName][dur];
//   let addonNamePrice = {};
//   addonNamePrice.addonName = addonName;
//   addonNamePrice.addonPrice = addonPrice;
//   addonNamePrice.addonDur = addonDur;
//   addonsPrices.push(addonNamePrice);
//   // update Prices Map
//   addonCarPrices.set("addons", addonsPrices);

//   const summaryAddonDiv = document.querySelector(".summary-step .summary");
//   const addonDiv = document.createElement("div");
//   const addonNameSp = document.createElement("span");
//   const addonPriceSp = document.createElement("span");
//   addonDiv.classList.add("addon", "p-3", "mb-2", "mb-md-2", "d-flex", "align-items-center");
//   addonDiv.id = check.value;

//   addonNameSp.classList.add("me-auto", "addon-name");
//   addonPriceSp.classList.add("ms-auto", "addon-price");
//   addonNameSp.innerHTML = addonData[addonName]["name"];
//   addonPriceSp.innerHTML = `+$${addonPrice}/${addonDur}`;
//   addonDiv.append(addonNameSp, addonPriceSp);
//   summaryAddonDiv.append(addonDiv);
// }
// function removeAddon(id) {

//   document.getElementById(id).remove();

//   let [, name] = id.split("-");
//   let addonName = name.toLowerCase();

//   addonCarPrices.get("addons").forEach((element, ind, arr) => {
//     if (element.addonName === addonName) {
//       arr.splice(ind, 1);
//     }
//   });
// }

// function totalCalc() {

//   const totalSummary = document.querySelector(".summary-step");
//   const totalDiv = document.createElement("div");
//   totalDiv.classList.add("total", "p-3", "mb-2", "mb-md-3", "d-flex", "align-items-center");
//   const totalDur = document.createElement("span");
//   const totalPrice = document.createElement("span");
//   totalDur.classList.add("me-auto", "total-dur");
//   totalPrice.classList.add("ms-auto", "total-price");
//   totalDur.innerHTML = `Total Per (${addonCarPrices.get("carType")})`;
//   totalPrice.innerHTML = doCalc();
//   totalDiv.append(totalDur, totalPrice);
//   totalSummary.append(totalDiv);
// }

// function doCalc() {
//   let carPrice = addonCarPrices.get("carPrice");
//   let addonsPrices = 0;

//   if (addonCarPrices.has("addons")) {
//     addonsPrices = addonCarPrices.get("addons").reduce((sum, curr) => {
//       return sum + curr.addonPrice;
//     }, 0);
//   }
//   return `${carPrice + addonsPrices}/${addonCarPrices.get("carType")}`;
// }


