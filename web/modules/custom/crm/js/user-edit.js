(function (Drupal, once) {
  "use strict";

  function createCard(title, description) {
    const card = document.createElement("section");
    card.className = "crm-user-edit-card";

    const header = document.createElement("div");
    header.className = "crm-user-edit-card__header";

    const heading = document.createElement("h2");
    heading.className = "crm-user-edit-card__title";
    heading.textContent = title;
    header.appendChild(heading);

    if (description) {
      const text = document.createElement("p");
      text.className = "crm-user-edit-card__description";
      text.textContent = description;
      header.appendChild(text);
    }

    const body = document.createElement("div");
    body.className = "crm-user-edit-card__body";

    card.appendChild(header);
    card.appendChild(body);

    return { card, body };
  }

  function findTopLevelElement(form, selectors) {
    for (const selector of selectors) {
      const element = form.querySelector(selector);
      if (!element) {
        continue;
      }

      const wrapper = element.closest(
        "details, fieldset, .js-form-wrapper, .form-wrapper, .js-form-item, .form-item",
      );
      if (wrapper && form.contains(wrapper)) {
        return wrapper;
      }
    }

    return null;
  }

  function hasVisibleControls(element) {
    if (!element) {
      return false;
    }

    return Boolean(
      element.querySelector(
        "input, select, textarea, button, details, fieldset, .form-managed-file, .form-checkboxes, .form-radios",
      ),
    );
  }

  function appendIfPresent(parent, element) {
    if (element && element.parentNode) {
      parent.appendChild(element);
    }
  }

  function buildAccountGrid(accountSection) {
    if (
      !accountSection ||
      accountSection.dataset.crmUserEditPrepared === "true"
    ) {
      return;
    }

    const groups = [
      [".form-item--current-pass", ".form-item--mail"],
      [".form-item--name", "#edit-pass"],
    ];

    groups.forEach(([firstSelector, secondSelector]) => {
      const first = accountSection.querySelector(firstSelector);
      const second = accountSection.querySelector(secondSelector);

      if (
        !first ||
        !second ||
        first.closest(".crm-user-edit-grid") ||
        second.closest(".crm-user-edit-grid")
      ) {
        return;
      }

      const row = document.createElement("div");
      row.className = "crm-user-edit-grid";
      first.parentNode.insertBefore(row, first);
      row.appendChild(first);
      row.appendChild(second);
    });

    accountSection.dataset.crmUserEditPrepared = "true";
  }

  Drupal.behaviors.crmUserEdit = {
    attach(context) {
      once("crm-user-edit", ".crm-user-edit-form", context).forEach((form) => {
        const hero = form.querySelector(".crm-user-edit-hero");
        const actions = form.querySelector("#edit-actions");

        const shell = document.createElement("div");
        shell.className = "crm-user-edit-shell";

        const mainColumn = document.createElement("div");
        mainColumn.className =
          "crm-user-edit-column crm-user-edit-column--main";

        const sideColumn = document.createElement("div");
        sideColumn.className =
          "crm-user-edit-column crm-user-edit-column--side";

        shell.appendChild(mainColumn);
        shell.appendChild(sideColumn);

        if (hero) {
          hero.insertAdjacentElement("afterend", shell);
        } else {
          form.insertBefore(shell, form.firstChild);
        }

        const sections = [
          {
            column: mainColumn,
            title: "Account details",
            description:
              "Manage login identity and password changes in the same streamlined layout as the rest of the CRM.",
            selectors: ['[data-drupal-selector="edit-account"]'],
            prepare: buildAccountGrid,
          },
          {
            column: mainColumn,
            title: "Locale preferences",
            description:
              "Choose the language and timezone used across your daily workflow.",
            selectors: [
              '[data-drupal-selector="edit-language"]',
              '[data-drupal-selector="edit-timezone"]',
            ],
          },
          {
            column: sideColumn,
            title: "Profile photo",
            description:
              "Keep your avatar aligned with the CRM directory and activity feed.",
            selectors: ["#edit-user-picture-wrapper"],
          },
          {
            column: sideColumn,
            title: "Team assignment",
            description:
              "Link this account to the correct team for ownership and visibility rules.",
            selectors: [
              '[data-drupal-selector="edit-field-team"]',
              '[data-drupal-selector^="edit-field-team"]',
            ],
          },
          {
            column: sideColumn,
            title: "Access controls",
            description:
              "Review status and roles without dropping back to the default Gin layout.",
            selectors: ["#edit-status--wrapper", "#edit-roles--wrapper"],
          },
          {
            column: sideColumn,
            title: "Admin theme settings",
            description: "Optional personal Gin preferences for this account.",
            selectors: ['[data-drupal-selector="edit-gin-theme-settings"]'],
          },
        ];

        sections.forEach((sectionConfig) => {
          const elements = sectionConfig.selectors
            .map((selector) => findTopLevelElement(form, [selector]))
            .filter(
              (element, index, all) =>
                element &&
                all.indexOf(element) === index &&
                hasVisibleControls(element),
            );

          if (!elements.length) {
            return;
          }

          const section = createCard(
            sectionConfig.title,
            sectionConfig.description,
          );
          sectionConfig.column.appendChild(section.card);

          elements.forEach((element) => appendIfPresent(section.body, element));

          if (typeof sectionConfig.prepare === "function") {
            sectionConfig.prepare(
              section.body.querySelector(
                '[data-drupal-selector="edit-account"]',
              ),
            );
          }
        });

        if (!mainColumn.children.length && !sideColumn.children.length) {
          shell.remove();
          return;
        }

        if (actions) {
          form.appendChild(actions);
        }
      });
    },
  };
})(Drupal, once);
