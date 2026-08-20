(() => {
  const defaultOrganizationLinks = new Set([
    'who we are',
    'mission & vision',
    'global presence',
    'fcra & registration'
  ]);
  const isAdditionalAboutLink = element =>
    !defaultOrganizationLinks.has(element.textContent.trim().toLowerCase());

  function moveBlogLinksToOtherLinks() {
    document.querySelectorAll('header').forEach(header => {
      if (header.dataset.blogAboutMenuActive !== 'true') return;
      const organizationHeading = [...header.querySelectorAll('p, div')]
        .find(element => element.textContent.trim() === 'Organization');
      if (!organizationHeading) return;

      const organizationColumn = organizationHeading.parentElement;
      const additionalLinks = [...organizationColumn.querySelectorAll('a')].filter(isAdditionalAboutLink);
      if (!additionalLinks.length) return;

      const columnsContainer = organizationColumn.parentElement;
      let otherLinksColumn = header.querySelector('[data-blog-other-links]');

      if (!otherLinksColumn) {
        otherLinksColumn = document.createElement('div');
        const heading = organizationHeading.cloneNode(false);
        heading.textContent = 'Other Links';
        const linksContainer = document.createElement('div');
        linksContainer.className = organizationHeading.nextElementSibling?.className || 'space-y-3';
        otherLinksColumn.append(heading, linksContainer);
        otherLinksColumn.dataset.blogOtherLinks = 'true';
        columnsContainer.appendChild(otherLinksColumn);
      }

      const linksContainer = otherLinksColumn.lastElementChild;
      const existingLinks = new Set(
        [...linksContainer.querySelectorAll('a')].map(link => `${link.textContent.trim()}|${link.href}`)
      );
      additionalLinks.forEach(link => {
        const linkKey = `${link.textContent.trim()}|${link.href}`;
        if (existingLinks.has(linkKey)) {
          link.remove();
          return;
        }
        existingLinks.add(linkKey);
        linksContainer.appendChild(link);
      });

      // The original About menu reserves two columns for links. Expand that
      // area to three columns so "Other Links" stays beside Leadership,
      // rather than wrapping beneath Organization.
      const megaMenuGrid = columnsContainer.parentElement;
      megaMenuGrid.dataset.blogMenuGrid = 'true';
      megaMenuGrid.style.gridTemplateColumns = 'repeat(5, minmax(0, 1fr))';
      columnsContainer.style.gridColumn = 'span 3 / span 3';
      columnsContainer.style.display = 'grid';
      columnsContainer.style.gridTemplateColumns = 'repeat(3, minmax(0, 1fr))';
      organizationColumn.style.gridColumn = '1';
      organizationColumn.style.gridRow = '1';
      const leadershipColumn = [...columnsContainer.children].find(column =>
        [...column.querySelectorAll('p, div')].some(element => element.textContent.trim() === 'Leadership')
      );
      if (leadershipColumn) {
        leadershipColumn.style.gridColumn = '2';
        leadershipColumn.style.gridRow = '1';
      }
      otherLinksColumn.style.gridColumn = '3';
      otherLinksColumn.style.gridRow = '1';
    });
  }

  function clearBlogMenuLayout(header) {
    header.querySelectorAll('[data-blog-other-links]').forEach(column => column.remove());
    header.querySelectorAll('[data-blog-menu-grid]').forEach(grid => {
      grid.style.gridTemplateColumns = '';
      grid.removeAttribute('data-blog-menu-grid');
      [...grid.children].forEach(column => {
        column.style.gridColumn = '';
        column.style.gridTemplateColumns = '';
        column.style.display = '';
        [...column.children].forEach(child => {
          child.style.gridColumn = '';
          child.style.gridRow = '';
        });
      });
    });
  }

  function bindAboutMenuOnly() {
    document.querySelectorAll('header').forEach(header => {
      if (header.dataset.blogHeaderBound !== 'true') {
        header.dataset.blogHeaderBound = 'true';
        header.addEventListener('mouseover', event => {
          const link = event.target.closest('nav a');
          if (!link) return;
          if (link.textContent.trim().startsWith('About')) {
            header.dataset.blogAboutMenuActive = 'true';
            setTimeout(moveBlogLinksToOtherLinks, 0);
          } else {
            header.dataset.blogAboutMenuActive = 'false';
            clearBlogMenuLayout(header);
          }
        });
      }
      const menuLinks = [...header.querySelectorAll('nav a')];
      const aboutLink = menuLinks.find(link => link.textContent.trim().startsWith('About'));
      if (!aboutLink) return;

      if (aboutLink.dataset.blogAboutBound !== 'true') {
        aboutLink.dataset.blogAboutBound = 'true';
        aboutLink.addEventListener('mouseenter', () => {
          header.dataset.blogAboutMenuActive = 'true';
          setTimeout(moveBlogLinksToOtherLinks, 0);
        });
      }
      menuLinks.filter(link => link !== aboutLink).forEach(link => {
        if (link.dataset.blogMenuBound === 'true') return;
        link.dataset.blogMenuBound = 'true';
        link.addEventListener('mouseenter', () => {
          header.dataset.blogAboutMenuActive = 'false';
          clearBlogMenuLayout(header);
        });
      });
    });
  }

  bindAboutMenuOnly();
  new MutationObserver(() => {
    bindAboutMenuOnly();
    moveBlogLinksToOtherLinks();
  }).observe(document.documentElement, {
    childList: true,
    subtree: true
  });
})();

