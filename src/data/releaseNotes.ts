export type ReleaseChangeType = "feature" | "improvement" | "fix" | "removed";

export interface ReleaseChange {
  type: ReleaseChangeType;
  text: string;
}

export interface Release {
  version: string;
  date: string;
  title: string;
  summary: string;
  changes: ReleaseChange[];
}

export const releases: Release[] = [
  {
    version: "0.5.1-alpha",
    date: "2026-06-30",
    title: "Projects-first dashboard & a cleaner sidebar",
    summary:
      "This release reworks the way you navigate the Control Center. The dashboard is now a focused projects overview, the sidebar is grouped into clearer sections, and a few legacy modules have been retired.",
    changes: [
      {
        type: "feature",
        text: 'Redesigned the "Create new table" page with a clearer, step-based layout.',
      },
      {
        type: "feature",
        text: "Turned the dashboard into a projects overview so you land on your work right away.",
      },
      {
        type: "improvement",
        text: "Reorganized the sidebar into Tools, Bookmarks and Projects sections.",
      },
      {
        type: "improvement",
        text: "Merged the default page into the unified page view for more consistent routing.",
      },
      {
        type: "removed",
        text: "Removed the NFC, QR code and Telegram bot modules to streamline the app.",
      },
    ],
  },
  {
    version: "0.5.0-alpha",
    date: "2026-06-10",
    title: "Collapsible sidebar & navigation polish",
    summary:
      "A round of navigation improvements, including a collapsible sidebar with hover tooltips and refined section dividers.",
    changes: [
      {
        type: "feature",
        text: "Added a collapsible sidebar with icon-only mode and hover tooltips.",
      },
      {
        type: "improvement",
        text: "Refined active-item highlighting and section dividers throughout the menu.",
      },
      {
        type: "fix",
        text: "Fixed several routing edge cases when switching between projects.",
      },
    ],
  },
];

export const currentRelease: Release = releases[0];
