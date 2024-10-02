const fs = require("fs")
const path = require("path")
const dotenv = require("dotenv")
dotenv.config()

const packageSlug = process.env.SLUG
if (!packageSlug) {
  console.log("Please provide a package slug")
  process.exit(1)
}
const sourceFolderPath = "./built/" + packageSlug + "-pro"

const writeFileRecursive = (file, data) => {
  const dirname = path.dirname(file)
  if (!fs.existsSync(dirname)) {
    fs.mkdirSync(dirname, { recursive: true })
  }
  fs.writeFileSync(file, data)
}


function addProToPluginName(sourceFilePath, destinationFilePath) {
  const content = fs.readFileSync(sourceFilePath, 'utf8');
  const lines = content.split('\n');
  let modifiedContent = '';

  for (let line of lines) {
    if (line.includes('* Plugin Name:')) {
      line = line.trimEnd() + ' Pro';
      console.log('Modified line:', line);
    }
    modifiedContent += line + '\n';
  }

  writeFileRecursive(destinationFilePath, modifiedContent.trim());
}

function processFilesInRoot(sourceFolderPath, destinationFolderPath) {
  const files = fs.readdirSync(sourceFolderPath)
  files.forEach((file) => {
    const sourceFilePath = path.join(sourceFolderPath, file)
    const destinationFilePath = path.join(destinationFolderPath, file)
    const ext = path.extname(sourceFilePath)

    if (ext === ".php") {
      addProToPluginName(
        sourceFilePath2,
        destinationFilePath
      )
      console.log("processFilesInRoot File written to : ", destinationFilePath);
    }

    if (fs.statSync(sourceFilePath).isDirectory()) {
      const subfolderName = path.basename(sourceFilePath)
      const destinationSubfolderPath = path.join(
        destinationFolderPath,
        subfolderName
      )
      fs.mkdirSync(destinationSubfolderPath, { recursive: true })
      processFilesInRoot(sourceFilePath, destinationSubfolderPath)
    }
  })
}

processFilesInRoot(sourceFolderPath, sourceFolderPath);