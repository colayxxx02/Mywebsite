function addRecommendation() {
    let msg = document.getElementById("new_recommendation");
    
    if (msg.value != null && msg.value.trim() != "") {
      var element = document.createElement("div");
      element.setAttribute("class", "recommendation");
      element.innerHTML = "<span>&#8220;</span>" + msg.value + "<span>&#8221;</span>";
      
      document.getElementById("all_recommendations").appendChild(element); 
      msg.value = "";
      showPopup(true);
    }
  }
  
  function showPopup(bool) {
    if (bool) {
      document.getElementById('popup').style.visibility = 'visible';
    } else {
      document.getElementById('popup').style.visibility = 'hidden';
    }
  }