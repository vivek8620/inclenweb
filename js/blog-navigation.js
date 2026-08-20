(() => {
  const isBlogLink = element => /^Blog\s*\d*\s*:/i.test(element.textContent.trim());

  function moveBlogLinksToOtherLinks() {
    document.querySelectorAll('header').forEach(header => {
      const organizationHeading = [...header.querySelectorAll('p, div')]
        .find(element => element.textContent.trim() === 'Organization');
      if (!organizationHeading) return;

      const organizationColumn = organizationHeading.parentElement;
      const blogLinks = [...organizationColumn.querySelectorAll('a')].filter(isBlogLink);
      if (!blogLinks.length) return;

      const columnsContainer = organizationColumn.parentElement;
      let otherLinksColumn = [...columnsContainer.children].find(column =>
        [...column.querySelectorAll('p, div')].some(element => element.textContent.trim() === 'Other Links')
      );

      if (!otherLinksColumn) {
        otherLinksColumn = document.createElement('div');
        const heading = organizationHeading.cloneNode(false);
        heading.textContent = 'Other Links';
        const linksContainer = document.createElement('div');
        linksContainer.className = organizationHeading.nextElementSibling?.className || 'space-y-3';
        otherLinksColumn.append(heading, linksContainer);
        columnsContainer.appendChild(otherLinksColumn);
      }

      const linksContainer = otherLinksColumn.lastElementChild;
      blogLinks.forEach(link => linksContainer.appendChild(link));
    });
  }

  moveBlogLinksToOtherLinks();
  new MutationObserver(moveBlogLinksToOtherLinks).observe(document.documentElement, {
    childList: true,
    subtree: true
  });
})();

