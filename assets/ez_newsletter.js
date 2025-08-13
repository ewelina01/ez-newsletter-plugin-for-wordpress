document.getElementById('ez-newsletter-open-modal').addEventListener('click', function(){
    let newsletterForm = document.getElementById('ez-newsletter-form');
    
    document.getElementById('ez-newsletter-modal').style.display = 'block';
    newsletterForm.style.display = "block";
    newsletterForm.parentElement.querySelector('h2').style.display = "block";
    newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-error').style.display = "none";
    newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-success').style.display = "none";
});
document.getElementById('ez-newsletter-close-modal').addEventListener('click', function(){
    document.getElementById('ez-newsletter-modal').style.display = 'none';
    document.getElementById('ez-newsletter-form-message').innerHTML = '';
    document.getElementById('ez-newsletter-newsletter-form').reset();
});

    
document.addEventListener("DOMContentLoaded", function () {
    let newsletterForm = document.getElementById('ez-newsletter-form');
    
    //wygaszam b³êdy
    newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-error').style.display = "none";
    newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-success').style.display = "none";
    

    if (newsletterForm) {
      newsletterForm.addEventListener("submit", function (e) {
        e.preventDefault();
        
        let email = newsletterForm.querySelector('#ez_email').value;

        fetch(ez_ajax.ajax_url, {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            action: "ez_newsletter_save_subscriber",
            email: email
          })
        })
        .then((res) => res.json())
        .then((data) => {
          console.log(data);
          
          if(data.success){
              newsletterForm.reset();
              newsletterForm.style.display = "none";
              newsletterForm.parentElement.querySelector('h2').style.display = "none";
              newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-success').style.display = "block";
              newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-success').innerHTML = data.data;
          } else {
              newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-error').style.display = "block";
              newsletterForm.parentElement.querySelector('#ez-newsletter-form-message-error').innerHTML = data.data;
          }
        });
      });
    }
});
    