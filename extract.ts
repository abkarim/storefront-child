import { execFileSync } from "node:child_process";
import fs from "node:fs/promises";
import path from "node:path";

const buildFolderName = "build";
const currentDir = process.cwd();
const buildDir = path.join(currentDir, buildFolderName);
const zipName = `${path.basename(currentDir)}.zip`;

const removeList = [
    ".git",
    ".gitignore",
    ".github",
    "extract.ts",
    "node_modules",
    "package.json",
    "package-lock.json",
    "style.scss",
    "style.css.map",
    zipName,
    "assets/ts/",
];

async function main() {
    console.log(`Preparing build directory: ${buildDir}`);
    await fs.rm(buildDir, { recursive: true, force: true });
    await fs.mkdir(buildDir, { recursive: true });

    console.log("Copying project files into build folder...");
    const entries = await fs.readdir(currentDir, { withFileTypes: true });
    for (const entry of entries) {
        if (entry.name === buildFolderName) {
            continue;
        }

        const source = path.join(currentDir, entry.name);
        const destination = path.join(buildDir, entry.name);
        await fs.cp(source, destination, {
            recursive: true,
            errorOnExist: false,
            dereference: true,
        });
    }

    console.log("Removing unwanted files from build folder...");
    await Promise.all(
        removeList.map(async (relativePath) => {
            const target = path.join(buildDir, relativePath);
            await fs.rm(target, { recursive: true, force: true });
        }),
    );

    console.log(`Creating ZIP archive: ${zipName}`);
    execFileSync("zip", ["-r", zipName, buildFolderName], {
        cwd: currentDir,
        stdio: "inherit",
    });

    console.log(`Build complete: ${zipName}`);
}

main().catch((error) => {
    console.error("Build failed:", error);
    process.exit(1);
});
