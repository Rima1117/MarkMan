function openExternalLinksInNewTab(element) {
    const links = element.querySelectorAll('a');
  
    links.forEach(link => {
      const href = link.getAttribute('href');
  
      if (href && href.startsWith('http') && !href.startsWith(window.location.origin)) {
        link.setAttribute('target', '_blank');
        link.setAttribute('rel', 'noopener noreferrer');
      }
    });
  }
  
  const contentElements = document.getElementsByClassName('post');
  if (contentElements.length > 0) {
    openExternalLinksInNewTab(contentElements[0]);
  }