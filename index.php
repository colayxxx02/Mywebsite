<!DOCTYPE html>
<html>
  <head>
    <title>Nica Polinar - Portfolio</title>
    <link rel="stylesheet" href="./style.css"/>
    <script src="./script.js"></script>
  </head>
  <body>
    <nav>
      <div id="home">
        <div class="profile_name">
          Nica Polinar
          <div class="contact_info">
            <img src="html_finalprojimages/envelope.png" alt="Email"/>
          jdoe@jeemail.com
        </div>
        <div style="clear:both;"></div>
        <div class="contact_info">
          <img src="html_finalprojimages/phone.png" alt="Phone"/>
          +13456764598
        </div>
        </div>
        <div class="topdiv">
          <a class="topmenu" href="#about-me">About Me</a>
          <a href="#skills" class="topmenu">Skills</a>
          <a href="#projects" class="topmenu">Projects</a>
          <a href="#recommendations" class="topmenu">Recommendations</a>
        </div>
      </div>    
    </nav>

    <section id="about-me" class="container">
      <div>
        <img src="https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg?auto=compress&cs=tinysrgb&w=300" alt="Profile Picture" class="profile_img">
        <h1>Hi, I'm Nica Polinar!</h1>
        <p class="about_text">
            I am a passionate Full-Stack Developer with a love for building clean, 
            functional, and user-centered digital experiences. With a background in 
            graphic design and three years of coding experience, I enjoy bridging 
            the gap between aesthetics and technical performance.
        </p>
      </div>
    </section>
              
    <section id="skills">
      <h2>Skills</h2>
      <div style="clear:both;"></div>
      <div class="all_skills">
        <div class="skill">
          <img src="html_finalprojimages/html5.png"/>
          <h6>HTML</h6>
          <p>2 years experience</p>
        </div>  
        <div class="skill">
          <img src="html_finalprojimages/js.jpeg"/>
          <h6>JavaScript</h6>
          <p>3 years experience</p>
        </div>  
        <div class="skill">
            <img src="html_finalprojimages/java.png"/>
            <h6>Java</h6>
            <p>4 years experience</p>
          </div> 
          <div class="skill">
            <img src="html_finalprojimages/react.png"/>
            <h6>React</h6>
            <p>4 years experience</p>
          </div> 
          <div class="skill">
            <img src="html_finalprojimages/node.png"/>
            <h6>Node.js</h6>
            <p>4 years experience</p>
          </div> 
          <div class="skill">
            <img src="html_finalprojimages/css.png"/>
            <h6>CSS</h6>
            <p>4 years experience</p>
          </div>
        </div>
    </section>
          
    <section class="projects" id="projects">
      <h2>Projects</h2>
      <div style="clear:both;"></div>
        <div id="projects-container" class="projects-container">
          <div class="project-card">
            <h3>Eco-Track Dashboard</h3>
            <ul>
                <li>Designed and developed a responsive React dashboard for monitoring household energy consumption in real-time.</li>
                <li>Integrated OpenWeather API to provide localized environmental data and energy-saving tips to over 500 active users.</li>
            </ul>
        </div>
        <hr> 
        <div class="project-card">
            <h3>Recipe Finder App</h3>
            <ul>
                <li>Built a mobile-first web application using JavaScript and CSS Grid that allows users to search for recipes by available ingredients.</li>
                <li>Implemented a local storage feature to save "Favorite" recipes, resulting in a 30% increase in user retention during testing.</li>
            </ul>
        </div>
        <hr>
        <div class="project-card">
            <h3>FinTech Landing Page</h3>
            <ul>
                <li>Created a high-conversion landing page for a fictitious startup using modern HTML5 and CSS animations.</li>
                <li>Optimized site performance to achieve a 95+ score on Google Lighthouse for both mobile and desktop views.</li>
            </ul>
          </div>
    </div>
    </section>
    <div style="clear:both;"></div>

    <section id="recommendations">
      <h2>Recommendations</h2>
      <div class="all_recommendations" id="all_recommendations">
    
        <div class="recommendation">
          <span>&#8220;</span>
          Sarah is an incredibly fast learner who mastered our stack in weeks. Her proactive attitude and ability to solve complex frontend bugs made her an invaluable asset to our team.
          <span>&#8221;</span>
        </div>
    
        <div class="recommendation">
          <span>&#8220;</span>
          Working with Sarah was a fantastic experience. She is highly detail-oriented and always goes the extra mile to ensure the UI is pixel-perfect. I would hire her again in a heartbeat.
          <span>&#8221;</span>
        </div>
    
        <div class="recommendation">
          <span>&#8220;</span>
          Sarah provided deep technical insight during our project’s initial phase. She is a committed developer who balances technical skill with great communication. She will be an asset to any organization!
          <span>&#8221;</span>
        </div>
    
      </div>
    </section>

    <section id="contact">
        <div class="flex_center">
          <fieldset>
            <legend class="introduction">Leave a Recommendation</legend>          
            <input type="text" id="recommend_name" placeholder="Name (Optional)"> <br/>
            <textarea id="new_recommendation" cols="50" rows="10" placeholder="Message"></textarea>
            <div class="flex_center">
              <button id="recommend_btn" onclick="addRecommendation()">Submit</button>
            </div>
          </fieldset>
        </div>
      </section>
  
      <div class="iconbutton">
        <a href="#home">
          <img src="html_finalprojimages/home.png" alt="Back to top" width="40px"/>
        </a>
      </div>
  
      <div class="popup" id="popup">
        <div class="flex_center">
          <img src="html_finalprojimages/checkmark--outline.svg"/>
          <h3>Thanks for leaving a recommendation!</h3> 
          <button onclick="showPopup(false)">Ok</button>
        </div>
      </div>
  </body>
</html>