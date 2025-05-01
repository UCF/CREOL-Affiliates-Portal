document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('affiliates-create-job-form');
    if (!form) return;
    
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        const formData = new FormData(this);
        const data = {
            title: formData.get('title'),
            job_description: formData.get('description'),
            contact: formData.get('contact')
        };

        fetch(affiliatesCreateJob.restUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': affiliatesCreateJob.nonce
            },
            credentials: 'include', // Ensure cookies are sent with the request
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            const responseElem = document.getElementById('job-response');
            responseElem.textContent = 'Job created successfully!';
            responseElem.classList.add('text-success');
            // Reset opacity in case it has been faded before
            responseElem.style.opacity = '1';

            form.reset(); // Reset the form fields

            // Fade out after 3 seconds
            setTimeout(() => {
                responseElem.style.transition = 'opacity 1s';
                responseElem.style.opacity = '0';
                // Clear the message after fading out
                setTimeout(() => {
                    responseElem.textContent = '';
                }, 1000);
            }, 3000);
            
            // Notify job list to refresh
            document.dispatchEvent(new CustomEvent('jobCreated'));
            console.log('Success:', data);
        })
        .catch(error => {
            const responseElem = document.getElementById('job-response');
            responseElem.textContent = 'Error creating job.';
            responseElem.classList.add('text-danger');
            responseElem.style.opacity = '1';
            setTimeout(() => {
                responseElem.style.transition = 'opacity 1s';
                responseElem.style.opacity = '0';
                setTimeout(() => {
                    responseElem.textContent = '';
                }, 1000);
            }, 3000);
            console.error('Error:', error);
        });
    });
});