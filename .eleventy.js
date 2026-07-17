module.exports = function (eleventyConfig) {
  eleventyConfig.addPassthroughCopy({
    "style.css": "style.css",
    "images": "images",
    "robots.txt": "robots.txt",
    "sitemap.xml": "sitemap.xml",
    "CNAME": "CNAME",
    "src/assets": "assets",
    "admin": "admin",
  });

  eleventyConfig.addGlobalData("year", () => new Date().getFullYear());

  return {
    dir: {
      input: "src",
      output: "_site",
      includes: "_includes",
      data: "_data",
    },
  };
};
