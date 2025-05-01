document.addEventListener('DOMContentLoaded', function() {
    const widget = document.getElementById('affiliates-portal-widget');
    if (!widget) return;
    const isSelf = widget.getAttribute('data-is-self') === '1';
    const originalRestUrl = affiliatesJobs.restUrl + (isSelf ? '?user_ids=' + encodeURIComponent(affiliatesJobs.currentUserId) : '');
    const jobList = document.getElementById('affiliates-job-list');
    let currentPage = 1;
    const perPage = 5;
    const paginationNav = document.getElementById('pagination-nav');

    function buildUrl(url, params) {
        const query = Object.keys(params)
            .map(key => key + '=' + encodeURIComponent(params[key]))
            .join('&');
        return url + (url.indexOf('?') === -1 ? '?' : '&') + query;
    }

    function loadJobList() {
        jobList.innerHTML = '';
        const urlWithPagination = buildUrl(originalRestUrl, { page: currentPage, per_page: perPage });
        fetch(urlWithPagination, { cache: 'no-store' })
            .then(response => {
                // Get total pages from header (WordPress REST API typically sends this header)
                const totalPages = parseInt(response.headers.get('X-WP-TotalPages'), 10) || 1;
                return response.json().then(data => ({ data, totalPages }));
            })
            .then(({ data, totalPages }) => {
                // Check if the API returned a message instead of the jobs array
                if (data.message) {
                    jobList.innerHTML = '<p>You have no jobs currently posted.</p>';
                    const existingPagination = document.getElementById('pagination-nav');
                    if (existingPagination) {
                        existingPagination.remove();
                    }
                    return;
                }
                
                const fragment = document.createDocumentFragment();
                data.forEach(function(job) {
                    const maxChars = 300;
                    const truncatedContent = job.content.length > maxChars ? job.content.substring(0, maxChars) + '...' : job.content;
                    const cardHTML = `
                        <div class="card mb-3">
                            <div class="card-block">
                                <h5 class="card-title">${job.title}</h5>
                                <p class="card-text">Posted By: ${job.author.name}</p>
                                <p class="card-text"><span class="text-muted">Contact: ${job.contact ? job.contact : 'N/A'}</span></p>
                                <p class="card-text">${truncatedContent}</p>
                                <div class="row justify-content-between">
                                    <div class="col-md-3 py-2">
                                        <button class="btn btn-secondary more-details-button w-100" data-id="${job.id}">More Details</button>
                                    </div>
                                ${ isSelf ? `
                                    <div class="col-md-3 py-2">
                                        <button class="btn btn-primary edit-button w-100" data-id="${job.id}">Edit</button>
                                    </div>
                                    <div class="col-md-3 py-2">
                                        <button class="btn btn-danger delete-button w-100" data-id="${job.id}">Delete</button>
                                    </div>
                                ` : '' }
                                </div>
                            </div>
                        </div>
                    `;
                    const temp = document.createElement('div');
                    temp.innerHTML = cardHTML.trim();
                    fragment.appendChild(temp.firstElementChild);
                });
                jobList.appendChild(fragment);
                renderPagination(totalPages);
            })
            .catch(error => console.error('Error fetching jobs:', error));
    }

    // New renderPagination function showing Prev, numbered links and Next.
    function renderPagination(totalPages) {
        // Remove any existing pagination nav
        let paginationNav = document.getElementById('pagination-nav');
        if (paginationNav) {
            paginationNav.remove();
        }
        
        // If there's only one page, no need to render pagination.
        if (totalPages <= 1) return;
        
        paginationNav = document.createElement('nav');
        paginationNav.id = 'pagination-nav';
        const ul = document.createElement('ul');
        ul.className = 'pagination justify-content-center';

        // Prev button
        const prevLi = document.createElement('li');
        prevLi.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
        const prevLink = document.createElement('a');
        prevLink.className = 'page-link';
        prevLink.href = '#';
        prevLink.textContent = 'Prev';
        if (currentPage > 1) {
            prevLink.addEventListener('click', function(event) {
                event.preventDefault();
                currentPage--;
                loadJobList();
            });
        }
        prevLi.appendChild(prevLink);
        ul.appendChild(prevLi);

        // Number links
        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement('li');
            li.className = 'page-item' + (i === currentPage ? ' active' : '');
            const link = document.createElement('a');
            link.className = 'page-link';
            link.href = '#';
            link.textContent = i;
            link.addEventListener('click', function(event) {
                event.preventDefault();
                currentPage = i;
                loadJobList();
            });
            li.appendChild(link);
            ul.appendChild(li);
        }

        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = 'page-item' + (currentPage === totalPages ? ' disabled' : '');
        const nextLink = document.createElement('a');
        nextLink.className = 'page-link';
        nextLink.href = '#';
        nextLink.textContent = 'Next';
        if (currentPage < totalPages) {
            nextLink.addEventListener('click', function(event) {
                event.preventDefault();
                currentPage++;
                loadJobList();
            });
        }
        nextLi.appendChild(nextLink);
        ul.appendChild(nextLi);

        paginationNav.appendChild(ul);
        // Append the pagination nav below the jobList container.
        jobList.parentNode.appendChild(paginationNav);
    }

    function fetchJobDetails(jobId) {
        // Remove pagination from the DOM if it exists
        const paginationElement = document.getElementById('pagination-nav');
        if (paginationElement) {
            paginationElement.remove();
        }
    
        // Use the single job endpoint
        const detailUrl = `${affiliatesJobs.restUrl}/${jobId}`;
    
        jobList.innerHTML = '';
        fetch(detailUrl, { cache: 'no-store' })
            .then(response => response.json())
            .then(result => {
                let job = result;
                if (!job || !job.id) {
                    jobList.innerHTML = '<p>Job not found.</p>';
                    return;
                }
                const detailHTML = `
                    <div class="card mb-3">
                        <div class="card-block">
                            <h5 class="card-title">${job.title}</h5>
                            <p class="card-text">Posted By: ${job.author}</p>
                            <p class="card-text"><span class="text-muted">Contact: ${job.contact ? job.contact : 'N/A'}</span></p>
                            <p class="card-text">${job.job_description ? job.job_description : job.content}</p>
                            <button class="btn btn-secondary back-button">Back</button>
                        </div>
                    </div>
                `;
                jobList.innerHTML = detailHTML;
            })
            .catch(error => console.error('Error fetching job details:', error));
    }

    // Function to show the edit form, prepopulated with the current job data.
    function showEditForm(job) {
        // Remove pagination if it exists
        let paginationNav = document.getElementById('pagination-nav');
        if (paginationNav) {
            // Hide the pagination
            paginationNav.style.display = 'none';
        }

        jobList.innerHTML = `
            <div class="card mb-3">
                <div class="card-block">
                    <h5>Edit Job</h5>
                    <form id="edit-job-form">
                        <div class="form-group">
                            <label for="edit-title">Job Title:</label>
                            <input type="text" class="form-control" id="edit-title" name="title" value="${job.title}" required>
                        </div>
                        <div class="form-group">
                            <label for="edit-description">Job Description:</label>
                            <textarea class="form-control" id="edit-description" name="job_description" style="height: 20vh;" required>${job.job_description}</textarea>
                        </div>
                        <div class="form-group">
                            <label for="edit-contact">Contact:</label>
                            <input type="text" class="form-control" id="edit-contact" name="contact" value="${job.contact ? job.contact : ''}" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                        <button type="button" class="btn btn-secondary cancel-edit">Cancel</button>
                    </form>
                </div>
            </div>
        `;
        
        const editForm = document.getElementById('edit-job-form');
        
        // Handle form submission
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(editForm);
            const data = {
                title: formData.get('title'),
                job_description: formData.get('job_description'),
                contact: formData.get('contact')
            };
            fetch(`${affiliatesJobs.restUrl}/${job.id}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': affiliatesJobs.nonce
                },
                credentials: 'include',
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                console.log('Job updated:', data);
                loadJobList();
                // Remove pagination if it exists
                let paginationNav = document.getElementById('pagination-nav');
                if (paginationNav) {
                    // Display the pagination
                    paginationNav.style.display = 'block';
                }
            })
            .catch(error => console.error('Error updating job:', error));
        });
        
        // Handle cancel action
        document.querySelector('.cancel-edit').addEventListener('click', function() {
            loadJobList();
        });
    }

    // New editJob() function that fetches the current job and then shows the edit form.
    function editJob(jobId) {
        // Fetch the current job data
        fetch(`${affiliatesJobs.restUrl}/${jobId}`, { cache: 'no-store' })
            .then(response => response.json())
            .then(job => {
                // If the API returns a wrapped result or an array, get the correct details.
                if (Array.isArray(job)) {
                    job = job.find(j => j.id == jobId);
                }
                if (!job) {
                    jobList.innerHTML = '<p>Job not found.</p>';
                    return;
                }
                showEditForm(job);
            })
            .catch(error => console.error('Error fetching job details for edit:', error));
    }

    // New deleteJob function that shows the modal instead of using confirm()
    function deleteJob(jobId) {
        // Store jobId in the modal's dataset for later reference
        const deleteModal = $('#deleteJobModal');
        deleteModal.data('jobId', jobId);
        deleteModal.modal('show');
    }

    // Attach click event to modal's confirm button once (if not already attached)
    document.getElementById('confirm-delete-btn').addEventListener('click', function(){
        const deleteModal = $('#deleteJobModal');
        const jobId = deleteModal.data('jobId');
        fetch(`${affiliatesJobs.restUrl}/${jobId}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': affiliatesJobs.nonce
            },
            credentials: 'include'
        })
        .then(response => response.json())
        .then(data => {
            console.log('Job deleted:', data);
            loadJobList();
            deleteModal.modal('hide');
        })
        .catch(error => console.error('Error deleting job:', error));
    });

    jobList.addEventListener('click', function(event) {
        if (event.target.matches('.more-details-button')) {
            const jobId = event.target.getAttribute('data-id');
            fetchJobDetails(jobId);
        } else if (event.target.matches('.back-button')) {
            loadJobList();
        } else if (event.target.matches('.edit-button')) {
            const jobId = event.target.getAttribute('data-id');
            editJob(jobId);
        } else if (event.target.matches('.delete-button')) {
            const jobId = event.target.getAttribute('data-id');
            deleteJob(jobId);
        }
    });

    // Listen for jobCreated event to refresh the list.
    document.addEventListener('jobCreated', function() {
        loadJobList();
    });

    loadJobList();
});